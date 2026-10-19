<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\Calendar\CalendarSyncProvider;
use App\Domain\CRM\ClientService;
use App\Domain\CRM\LeadService;
use App\Domain\Firm\FirmService;
use App\Domain\Notifications\NotificationService;
use App\Domain\Scheduling\AvailabilityService;
use App\Domain\Scheduling\FirmTime;
use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Call;
use App\Models\Lawyer;
use App\Models\Scopes\FirmScope;
use App\Models\Service;
use App\Support\BookingResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookAppointment
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly LeadService $leads,
        private readonly ClientService $clients,
        private readonly FirmService $firmService,
        private readonly AuditLogService $audit,
        private readonly NotificationService $notifications,
        private readonly FirmTime $clock,
        private readonly CalendarSyncProvider $calendars,
    ) {}

    public function execute(array $payload): BookingResult
    {
        $service = Service::query()->find($payload['service_id'] ?? null);
        $type = AppointmentType::query()->find($payload['appointment_type_id'] ?? null);
        $lawyer = Lawyer::query()->find($payload['lawyer_id'] ?? null);

        if (! $lawyer || (! $service && ! $type)) {
            return BookingResult::fail('INVALID_INPUT', 'A lawyer and an appointment type or service are required.');
        }

        $payload = $this->normalizePhoneAiPayload($payload);

        // Same call / same idempotency key → return the existing row (true retry).
        $existing = $this->findExactPhoneAiRetry($payload);
        if ($existing) {
            $this->cancelPendingAiForCaller($payload, $existing->id);

            return BookingResult::ok($existing->loadMissing(['lawyer.user', 'service', 'appointmentType', 'lead']));
        }

        $firm = $this->firmService->current();
        $duration = AvailabilityService::SLOT_MINUTES;
        $startUtc = $this->clock->toUtc($payload['start_at'], $firm);
        $endUtc = $startUtc->copy()->addMinutes($duration);
        $bookable = $type ?? $service;
        $lockKey = sprintf('appointment:%d:%s', $lawyer->id, $startUtc->format('YmdHi'));
        $callLockKey = $this->phoneAiCallLockKey($payload);

        try {
            $appointment = DB::transaction(function () use ($payload, $service, $type, $lawyer, $startUtc, $endUtc, $bookable, $lockKey, $callLockKey) {
                if ($callLockKey !== null) {
                    $this->acquireLock($callLockKey);
                }
                $this->acquireLock($lockKey);

                // Re-check exact retry inside the lock.
                $existing = $this->findExactPhoneAiRetry($payload);
                if ($existing) {
                    $this->cancelPendingAiForCaller($payload, $existing->id);

                    return $existing;
                }

                // One active AI booking per caller: clear older pending rows first (last wins).
                $this->cancelPendingAiForCaller($payload);

                Lawyer::query()->whereKey($lawyer->id)->lockForUpdate()->first();
                Appointment::query()
                    ->where('lawyer_id', $lawyer->id)
                    ->whereNotNull('active_slot_key')
                    ->lockForUpdate()
                    ->get(['id']);

                if (! $this->availability->isSlotAvailable($bookable, $lawyer, $startUtc->toIso8601String())) {
                    return null;
                }

                $lead = $this->leads->createOrUpdateFromCaller([
                    'name' => $payload['name'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'service_id' => $service?->id,
                    'lawyer_id' => $lawyer->id,
                    'notes' => $payload['notes'] ?? $payload['case_summary'] ?? null,
                    'source' => $payload['source'] ?? 'phone_ai',
                    'status' => LeadStatus::ConsultationBooked->value,
                ]);

                // Call customers become Clients so they appear under Clients.
                $client = null;
                if (! empty($payload['client_id'])) {
                    $client = \App\Models\Client::query()->find($payload['client_id']);
                }
                $client ??= $this->clients->upsertFromCaller([
                    'name' => $payload['name'] ?? $lead->name,
                    'phone' => $payload['phone'] ?? $lead->phone,
                    'email' => $payload['email'] ?? $lead->email,
                    'notes' => $payload['notes'] ?? $payload['case_summary'] ?? $lead->notes,
                    'service_id' => $service?->id,
                ]);

                if ($client && $lead->id) {
                    $client->leads()->syncWithoutDetaching([$lead->id]);
                }

                $callId = $payload['call_id'] ?? null;
                if (! $callId && ! empty($payload['provider_call_id'])) {
                    $callId = Call::query()->withoutGlobalScope(FirmScope::class)
                        ->where('provider_call_id', $payload['provider_call_id'])
                        ->value('id');
                }

                if ($callId) {
                    Call::query()->withoutGlobalScope(FirmScope::class)
                        ->whereKey($callId)
                        ->update(array_filter([
                            'lead_id' => $lead->id,
                            'client_id' => $client?->id,
                            'lawyer_id' => $lawyer->id,
                        ]));
                }

                $status = $this->requestedStatus($payload['status'] ?? null);

                return Appointment::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'lead_id' => $lead->id,
                    'client_id' => $client?->id,
                    'call_id' => $callId,
                    'service_id' => $service?->id,
                    'appointment_type_id' => $type?->id,
                    'start_at' => $startUtc,
                    'end_at' => $endUtc,
                    'status' => $status,
                    'source' => $payload['source'] ?? 'phone_ai',
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'case_summary' => $payload['case_summary'] ?? null,
                    'fee_amount' => $type ? $type->feeFor($lawyer) : $service?->price,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            $recovered = $this->findExactPhoneAiRetry($payload);
            if ($recovered) {
                $this->cancelPendingAiForCaller($payload, $recovered->id);

                return BookingResult::ok($recovered->loadMissing(['lawyer.user', 'service', 'appointmentType', 'lead']));
            }

            return BookingResult::conflict();
        } catch (Throwable $e) {
            Log::error('Booking failed', ['error' => $e->getMessage()]);

            return BookingResult::fail('BOOKING_ERROR', 'Unable to complete booking.');
        } finally {
            $this->releaseLock($lockKey);
            if ($callLockKey !== null) {
                $this->releaseLock($callLockKey);
            }
        }

        if (! $appointment instanceof Appointment) {
            $alternatives = $service
                ? array_slice($this->availability->findSlots($service, $startUtc, $lawyer), 0, 5)
                : array_slice($this->availability->bookableSlots($lawyer, $type, $startUtc, $startUtc), 0, 5);

            return BookingResult::conflict('Slot is no longer available.', $alternatives);
        }

        // Keep only this AI booking for the caller (drop older pending/completed too).
        if (($payload['source'] ?? null) === 'phone_ai') {
            $this->cancelPendingAiForCaller([
                ...$payload,
                'client_id' => $appointment->client_id,
                'lead_id' => $appointment->lead_id,
                'call_id' => $appointment->call_id ?? ($payload['call_id'] ?? null),
            ], $appointment->id);
        }

        // Existing row returned from a retry / second tool call — do not re-notify.
        if ($appointment->wasRecentlyCreated) {
            $this->audit->log('appointment.booked', $appointment, null, $appointment->toArray());
            $this->notifications->queueAppointmentBooked($appointment);
            $this->calendars->upsert($appointment);
        }

        return BookingResult::ok($appointment->load(['lawyer.user', 'service', 'appointmentType', 'lead']));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePhoneAiPayload(array $payload): array
    {
        $source = (string) ($payload['source'] ?? 'staff');
        if ($source !== 'phone_ai') {
            return $payload;
        }

        $callId = isset($payload['call_id']) && $payload['call_id'] !== '' ? (int) $payload['call_id'] : null;
        if (! $callId && ! empty($payload['provider_call_id'])) {
            $callId = Call::query()->withoutGlobalScope(FirmScope::class)
                ->where('provider_call_id', $payload['provider_call_id'])
                ->value('id');
            if ($callId) {
                $payload['call_id'] = (int) $callId;
            }
        }

        if (empty($payload['idempotency_key'])) {
            if (! empty($payload['call_id'])) {
                $payload['idempotency_key'] = 'phone_ai:call:'.(int) $payload['call_id'];
            } elseif (! empty($payload['provider_call_id'])) {
                $payload['idempotency_key'] = 'phone_ai:provider:'.trim((string) $payload['provider_call_id']);
            } else {
                $digits = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? '')) ?: '';
                if ($digits !== '') {
                    // Fallback when call id is missing — one AI booking per phone per hour.
                    $payload['idempotency_key'] = 'phone_ai:phone:'.$digits.':'.now()->format('YmdH');
                }
            }
        }

        return $payload;
    }

    /**
     * True retry only: same call / provider call / idempotency key.
     *
     * @param  array<string, mixed>  $payload
     */
    private function findExactPhoneAiRetry(array $payload): ?Appointment
    {
        if (($payload['source'] ?? null) !== 'phone_ai') {
            return null;
        }

        if (! empty($payload['idempotency_key'])) {
            $byKey = Appointment::query()
                ->where('idempotency_key', $payload['idempotency_key'])
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->first();
            if ($byKey) {
                return $byKey;
            }
        }

        if (! empty($payload['call_id'])) {
            $byCall = Appointment::query()
                ->where('call_id', (int) $payload['call_id'])
                ->where('source', 'phone_ai')
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->orderBy('id')
                ->first();
            if ($byCall) {
                return $byCall;
            }
        }

        if (! empty($payload['provider_call_id'])) {
            return Appointment::query()
                ->where('source', 'phone_ai')
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->whereHas('call', function ($q) use ($payload) {
                    $q->withoutGlobalScope(FirmScope::class)
                        ->where('provider_call_id', $payload['provider_call_id']);
                })
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    /**
     * Keep ONE AI booking per caller: remove every older phone_ai row for the same
     * call / phone / client / lead (any status, including completed). Last book wins.
     *
     * @param  array<string, mixed>  $payload
     */
    private function cancelPendingAiForCaller(array $payload, ?int $exceptId = null): void
    {
        if (($payload['source'] ?? null) !== 'phone_ai') {
            return;
        }

        $digits = $this->phoneDigits($payload['phone'] ?? null);
        $tail = strlen($digits) >= 9 ? substr($digits, -9) : $digits;
        $clientId = isset($payload['client_id']) ? (int) $payload['client_id'] : null;
        $leadId = isset($payload['lead_id']) ? (int) $payload['lead_id'] : null;

        $candidates = collect();

        $base = fn () => Appointment::query()
            ->with(['lead', 'client'])
            ->where('source', 'phone_ai')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId));

        if (! empty($payload['call_id'])) {
            $candidates = $candidates->merge(
                $base()->where('call_id', (int) $payload['call_id'])->get()
            );
        }

        if ($clientId) {
            $candidates = $candidates->merge($base()->where('client_id', $clientId)->limit(50)->get());
        }

        if ($leadId) {
            $candidates = $candidates->merge($base()->where('lead_id', $leadId)->limit(50)->get());
        }

        if ($digits !== '') {
            $candidates = $candidates->merge(
                $base()
                    ->where(function ($q) use ($digits, $tail) {
                        $q->whereHas('lead', function ($l) use ($digits, $tail) {
                            $l->where(function ($p) use ($digits, $tail) {
                                $p->whereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                                    ['%'.$digits.'%']
                                )->orWhereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                                    ['%'.$tail.'%']
                                );
                            });
                        })->orWhereHas('client', function ($c) use ($digits, $tail) {
                            $c->where(function ($p) use ($digits, $tail) {
                                $p->whereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                                    ['%'.$digits.'%']
                                )->orWhereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                                    ['%'.$tail.'%']
                                );
                            });
                        });
                    })
                    ->limit(50)
                    ->get()
            );
        }

        $candidates->unique('id')->each(function (Appointment $duplicate) use ($exceptId): void {
            if ($exceptId && (int) $duplicate->id === (int) $exceptId) {
                return;
            }

            $duplicate->delete();
        });
    }

    private function phoneDigits(mixed $phone): string
    {
        if (is_array($phone)) {
            $phone = $phone['value'] ?? $phone['number'] ?? $phone['phone'] ?? reset($phone) ?: '';
        }

        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function phoneAiCallLockKey(array $payload): ?string
    {
        if (($payload['source'] ?? null) !== 'phone_ai') {
            return null;
        }
        if (! empty($payload['call_id'])) {
            return 'phone-ai-call:'.(int) $payload['call_id'];
        }
        if (! empty($payload['provider_call_id'])) {
            return 'phone-ai-provider:'.trim((string) $payload['provider_call_id']);
        }

        return null;
    }

    private function requestedStatus(mixed $status): AppointmentStatus
    {
        $resolved = is_string($status) ? AppointmentStatus::tryFrom($status) : null;
        if ($resolved === AppointmentStatus::Pending || $resolved === AppointmentStatus::Confirmed) {
            return $resolved;
        }

        return AppointmentStatus::Pending;
    }

    private function acquireLock(string $key, int $timeoutSeconds = 5): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $result = DB::selectOne('SELECT GET_LOCK(?, ?) as locked', [substr(md5($key), 0, 64), $timeoutSeconds]);
        if (! $result || (int) $result->locked !== 1) {
            throw new \RuntimeException('Could not acquire booking lock.');
        }
    }

    private function releaseLock(string $key): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::select('SELECT RELEASE_LOCK(?)', [substr(md5($key), 0, 64)]);
        } catch (Throwable) {
            // The named lock expires with the connection if release fails.
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\Calendar\CalendarSyncProvider;
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

        if (! empty($payload['idempotency_key'])) {
            $existing = Appointment::query()->where('idempotency_key', $payload['idempotency_key'])->first();
            if ($existing) {
                return BookingResult::ok($existing);
            }
        }

        $firm = $this->firmService->current();
        $duration = (int) ($type?->duration_minutes ?? $service?->duration_minutes);
        $startUtc = $this->clock->toUtc($payload['start_at'], $firm);
        $endUtc = $startUtc->copy()->addMinutes($duration);
        $bookable = $type ?? $service;
        $lockKey = sprintf('appointment:%d:%s', $lawyer->id, $startUtc->format('YmdHi'));

        try {
            $appointment = DB::transaction(function () use ($payload, $service, $type, $lawyer, $startUtc, $endUtc, $bookable, $lockKey) {
                $this->acquireLock($lockKey);
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

                $callId = $payload['call_id'] ?? null;
                if (! $callId && ! empty($payload['provider_call_id'])) {
                    $callId = Call::query()->withoutGlobalScope(FirmScope::class)
                        ->where('provider_call_id', $payload['provider_call_id'])
                        ->value('id');
                }

                if ($callId && $lead->id) {
                    Call::query()->withoutGlobalScope(FirmScope::class)
                        ->whereKey($callId)
                        ->whereNull('lead_id')
                        ->update(['lead_id' => $lead->id]);
                }

                $status = $this->requestedStatus($payload['status'] ?? null);

                return Appointment::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'lead_id' => $lead->id,
                    'client_id' => $payload['client_id'] ?? null,
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
            return BookingResult::conflict();
        } catch (Throwable $e) {
            Log::error('Booking failed', ['error' => $e->getMessage()]);

            return BookingResult::fail('BOOKING_ERROR', 'Unable to complete booking.');
        } finally {
            $this->releaseLock($lockKey);
        }

        if (! $appointment instanceof Appointment) {
            $alternatives = $service
                ? array_slice($this->availability->findSlots($service, $startUtc, $lawyer), 0, 5)
                : array_slice($this->availability->bookableSlots($lawyer, $type, $startUtc, $startUtc), 0, 5);

            return BookingResult::conflict('Slot is no longer available.', $alternatives);
        }

        $this->audit->log('appointment.booked', $appointment, null, $appointment->toArray());
        $this->notifications->queueAppointmentBooked($appointment);
        $this->calendars->upsert($appointment);

        return BookingResult::ok($appointment->load(['lawyer.user', 'service', 'appointmentType', 'lead']));
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

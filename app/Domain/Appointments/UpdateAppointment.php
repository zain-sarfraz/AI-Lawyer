<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\Calendar\CalendarSyncProvider;
use App\Domain\CRM\LeadService;
use App\Domain\Scheduling\AvailabilityService;
use App\Domain\Scheduling\FirmTime;
use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\BookingResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAppointment
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly LeadService $leads,
        private readonly AuditLogService $audit,
        private readonly FirmTime $clock,
        private readonly CalendarSyncProvider $calendars,
    ) {}

    /**
     * @param  array{
     *     name?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     service_id: int|string,
     *     lawyer_id: int|string,
     *     start_at: string,
     *     notes?: ?string,
     *     status?: ?string
     * }  $payload
     */
    public function execute(Appointment $appointment, array $payload): BookingResult
    {
        $service = Service::query()->find($payload['service_id'] ?? null);
        $lawyer = Lawyer::query()->find($payload['lawyer_id'] ?? null);

        if (! $service || ! $lawyer) {
            return BookingResult::fail('INVALID_INPUT', 'A lawyer and service are required.');
        }

        $startUtc = $this->clock->toUtc($payload['start_at']);
        $endUtc = $startUtc->copy()->addMinutes(AvailabilityService::SLOT_MINUTES);
        $status = $this->requestedStatus($payload['status'] ?? null, $appointment->status);

        try {
            $updated = DB::transaction(function () use ($appointment, $payload, $service, $lawyer, $startUtc, $endUtc, $status) {
                Lawyer::query()->whereKey($lawyer->id)->lockForUpdate()->first();
                Appointment::query()
                    ->where('lawyer_id', $lawyer->id)
                    ->whereNotNull('active_slot_key')
                    ->lockForUpdate()
                    ->get(['id']);

                $slotChanged = (int) $appointment->lawyer_id !== (int) $lawyer->id
                    || ! $appointment->start_at
                    || ! $appointment->start_at->equalTo($startUtc)
                    || (int) $appointment->service_id !== (int) $service->id;

                if ($slotChanged && $status->isBlocking()) {
                    if (! $this->availability->isSlotAvailable($service, $lawyer, $startUtc->toIso8601String(), $appointment->id)) {
                        return null;
                    }
                }

                if ($appointment->lead) {
                    $this->leads->update($appointment->lead, array_filter([
                        'name' => $payload['name'] ?? $appointment->lead->name,
                        'phone' => $payload['phone'] ?? $appointment->lead->phone,
                        'email' => $payload['email'] ?? $appointment->lead->email,
                        'service_id' => $service->id,
                        'lawyer_id' => $lawyer->id,
                        'notes' => $payload['notes'] ?? $appointment->lead->notes,
                        'status' => LeadStatus::ConsultationBooked->value,
                        'last_contact_at' => now(),
                    ], fn ($v) => $v !== null));
                } elseif (! empty($payload['phone']) || ! empty($payload['name'])) {
                    $lead = $this->leads->createOrUpdateFromCaller([
                        'name' => $payload['name'] ?? null,
                        'phone' => $payload['phone'] ?? null,
                        'email' => $payload['email'] ?? null,
                        'service_id' => $service->id,
                        'lawyer_id' => $lawyer->id,
                        'notes' => $payload['notes'] ?? null,
                        'source' => $appointment->source ?: 'staff',
                        'status' => LeadStatus::ConsultationBooked->value,
                    ]);
                    $appointment->lead_id = $lead->id;
                }

                $old = $appointment->only([
                    'lawyer_id', 'service_id', 'start_at', 'end_at', 'status', 'notes', 'fee_amount',
                ]);

                $appointment->fill([
                    'lawyer_id' => $lawyer->id,
                    'service_id' => $service->id,
                    'start_at' => $startUtc,
                    'end_at' => $endUtc,
                    'status' => $status,
                    'notes' => $payload['notes'] ?? $appointment->notes,
                    'fee_amount' => $service->price,
                ]);
                $appointment->save();

                $this->audit->log('appointment.updated', $appointment, $old, $appointment->only([
                    'lawyer_id', 'service_id', 'start_at', 'end_at', 'status', 'notes', 'fee_amount',
                ]));

                return $appointment;
            });
        } catch (UniqueConstraintViolationException) {
            return BookingResult::conflict();
        } catch (Throwable $e) {
            Log::error('Appointment update failed', ['error' => $e->getMessage()]);

            return BookingResult::fail('UPDATE_ERROR', 'Unable to update appointment.');
        }

        if (! $updated instanceof Appointment) {
            return BookingResult::conflict('Slot is no longer available.');
        }

        $this->calendars->upsert($updated);

        return BookingResult::ok($updated->fresh(['lawyer.user', 'service', 'lead', 'client']));
    }

    private function requestedStatus(mixed $status, AppointmentStatus|string|null $fallback): AppointmentStatus
    {
        $resolved = is_string($status) ? AppointmentStatus::tryFrom($status) : null;
        if ($resolved) {
            return $resolved;
        }

        if ($fallback instanceof AppointmentStatus) {
            return $fallback;
        }

        return AppointmentStatus::tryFrom((string) $fallback) ?? AppointmentStatus::Pending;
    }
}

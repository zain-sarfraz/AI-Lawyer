<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\Calendar\CalendarSyncProvider;
use App\Domain\Scheduling\AvailabilityService;
use App\Domain\Scheduling\FirmTime;
use App\Domain\Scheduling\WaitlistService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\BookingResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RescheduleAppointment
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly AuditLogService $audit,
        private readonly FirmTime $clock,
        private readonly CalendarSyncProvider $calendars,
        private readonly WaitlistService $waitlist,
    ) {}

    public function execute(Appointment $appointment, string $newStartAt): BookingResult
    {
        $appointment->loadMissing(['service', 'appointmentType', 'lawyer']);
        $service = $appointment->service;
        $type = $appointment->appointmentType;
        $lawyer = $appointment->lawyer;

        if (! $lawyer || (! $service && ! $type)) {
            return BookingResult::fail('INVALID_INPUT', 'This appointment cannot be rescheduled.');
        }

        $duration = (int) ($type?->duration_minutes ?? $service?->duration_minutes);
        $startUtc = $this->clock->toUtc($newStartAt);
        $endUtc = $startUtc->copy()->addMinutes($duration);
        $bookable = $type ?? $service;
        $freedStart = $appointment->start_at->copy();

        try {
            $created = DB::transaction(function () use ($appointment, $lawyer, $service, $type, $startUtc, $endUtc, $bookable) {
                Lawyer::query()->whereKey($lawyer->id)->lockForUpdate()->first();
                Appointment::query()
                    ->where('lawyer_id', $lawyer->id)
                    ->whereNotNull('active_slot_key')
                    ->lockForUpdate()
                    ->get(['id']);

                if (! $this->availability->isSlotAvailable($bookable, $lawyer, $startUtc->toIso8601String(), $appointment->id)) {
                    return null;
                }

                $old = $appointment->only(['start_at', 'end_at', 'status', 'active_slot_key']);
                $appointment->update([
                    'status' => AppointmentStatus::Rescheduled,
                ]);
                $this->audit->log('appointment.rescheduled', $appointment, $old, $appointment->only(['status', 'active_slot_key']));

                return Appointment::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'lead_id' => $appointment->lead_id,
                    'client_id' => $appointment->client_id,
                    'service_id' => $service?->id,
                    'appointment_type_id' => $type?->id,
                    'rescheduled_from_id' => $appointment->id,
                    'start_at' => $startUtc,
                    'end_at' => $endUtc,
                    'status' => AppointmentStatus::Pending,
                    'source' => $appointment->source,
                    'notes' => $appointment->notes,
                    'case_summary' => $appointment->case_summary,
                    'fee_amount' => $appointment->fee_amount,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return BookingResult::conflict();
        }

        if (! $created instanceof Appointment) {
            return BookingResult::conflict();
        }

        $this->audit->log('appointment.booked', $created, null, $created->toArray());
        $this->calendars->remove($appointment);
        $this->calendars->upsert($created);
        $this->waitlist->notifyFirst($lawyer, $freedStart);

        return BookingResult::ok($created->fresh(['lawyer.user', 'service', 'appointmentType', 'client']));
    }
}

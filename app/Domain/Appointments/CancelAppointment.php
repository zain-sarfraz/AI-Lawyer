<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\Calendar\CalendarSyncProvider;
use App\Domain\Notifications\NotificationService;
use App\Domain\Scheduling\WaitlistService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class CancelAppointment
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NotificationService $notifications,
        private readonly CalendarSyncProvider $calendars,
        private readonly WaitlistService $waitlist,
    ) {}

    public function execute(Appointment $appointment, ?string $reason = null): Appointment
    {
        $old = $appointment->only(['status', 'notes', 'active_slot_key']);
        $freedStart = $appointment->start_at->copy();
        $lawyer = $appointment->lawyer;

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'notes' => trim(($appointment->notes ? $appointment->notes."\n" : '').($reason ? 'Cancel reason: '.$reason : '')),
        ]);

        $this->audit->log('appointment.cancelled', $appointment, $old, $appointment->only(['status', 'notes', 'active_slot_key']));
        $this->notifications->queueAppointmentCancelled($appointment);
        $this->calendars->remove($appointment);

        if ($lawyer) {
            $this->waitlist->notifyFirst($lawyer, $freedStart);
        }

        return $appointment->fresh();
    }
}

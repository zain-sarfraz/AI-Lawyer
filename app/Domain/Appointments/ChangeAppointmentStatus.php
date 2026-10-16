<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use InvalidArgumentException;

class ChangeAppointmentStatus
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly CancelAppointment $cancel,
    ) {}

    public function execute(Appointment $appointment, AppointmentStatus $status): Appointment
    {
        if ($status === AppointmentStatus::Cancelled) {
            return $this->cancel->execute($appointment, 'Status changed to cancelled');
        }

        if ($status === AppointmentStatus::Rescheduled) {
            throw new InvalidArgumentException('Reschedule creates a new appointment.');
        }

        $old = $appointment->only(['status', 'active_slot_key']);
        $appointment->update(['status' => $status]);
        $this->audit->log('appointment.status', $appointment, $old, $appointment->only(['status', 'active_slot_key']));

        return $appointment->fresh();
    }
}

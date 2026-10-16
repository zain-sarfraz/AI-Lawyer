<?php

declare(strict_types=1);

namespace App\Domain\Appointments;

use App\Models\Appointment;
use App\Support\BookingResult;

class AppointmentBookingService
{
    public function __construct(
        private readonly BookAppointment $bookAppointment,
        private readonly RescheduleAppointment $rescheduleAppointment,
        private readonly UpdateAppointment $updateAppointment,
        private readonly CancelAppointment $cancelAppointment,
    ) {}

    public function book(array $payload): BookingResult
    {
        return $this->bookAppointment->execute($payload);
    }

    public function reschedule(Appointment $appointment, string $newStartAt): BookingResult
    {
        return $this->rescheduleAppointment->execute($appointment, $newStartAt);
    }

    public function update(Appointment $appointment, array $payload): BookingResult
    {
        return $this->updateAppointment->execute($appointment, $payload);
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        return $this->cancelAppointment->execute($appointment, $reason);
    }
}

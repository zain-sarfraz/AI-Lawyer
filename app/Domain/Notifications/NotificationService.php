<?php

namespace App\Domain\Notifications;

use App\Jobs\SendAppointmentConfirmationJob;
use App\Models\Appointment;
use App\Models\NotificationLog;

class NotificationService
{
    public function queueAppointmentBooked(Appointment $appointment): void
    {
        SendAppointmentConfirmationJob::dispatch($appointment->id);
    }

    public function queueAppointmentCancelled(Appointment $appointment): void
    {
        $appointment->loadMissing('lead');
        $recipient = $appointment->lead?->email ?: $appointment->lead?->phone;
        if (! $recipient) {
            return;
        }

        NotificationLog::query()->create([
            'type' => 'appointment_cancelled',
            'channel' => filter_var($recipient, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms',
            'recipient' => $recipient,
            'related_type' => Appointment::class,
            'related_id' => $appointment->id,
            'status' => 'queued',
        ]);
    }
}

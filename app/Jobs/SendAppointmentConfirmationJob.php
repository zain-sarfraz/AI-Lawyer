<?php

namespace App\Jobs;

use App\Domain\Firm\FirmContext;
use App\Domain\Integrations\TwilioService;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\Scopes\FirmScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAppointmentConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $appointmentId) {}

    public function handle(TwilioService $twilio): void
    {
        $appointment = Appointment::query()
            ->withoutGlobalScope(FirmScope::class)
            ->find($this->appointmentId);

        if (! $appointment) {
            return;
        }

        app(FirmContext::class)->run((int) $appointment->firm_id, function () use ($appointment, $twilio): void {
            $this->deliver($appointment->load(['lead', 'service', 'lawyer.user']), $twilio);
        });
    }

    private function deliver(Appointment $appointment, TwilioService $twilio): void
    {
        $lead = $appointment->lead;
        $when = $appointment->start_at?->timezone(config('app.timezone'))->toDayDateTimeString();
        $service = $appointment->service?->name ?? 'consultation';
        $lawyer = $appointment->lawyer?->displayName() ?? 'our team';
        $body = "Your {$service} with {$lawyer} is booked for {$when}. Reply to this number if you need help.";

        if ($lead?->phone) {
            $log = NotificationLog::query()->create([
                'type' => 'appointment_booked',
                'channel' => 'sms',
                'recipient' => $lead->phone,
                'related_type' => Appointment::class,
                'related_id' => $appointment->id,
                'status' => 'queued',
            ]);

            $result = $twilio->sendSms($lead->phone, $body);
            $log->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_id' => $result['provider_id'] ?? null,
                'error_message' => $result['error'] ?? null,
            ]);
        }

        if ($lead?->email) {
            $log = NotificationLog::query()->create([
                'type' => 'appointment_booked',
                'channel' => 'email',
                'recipient' => $lead->email,
                'related_type' => Appointment::class,
                'related_id' => $appointment->id,
                'status' => 'queued',
            ]);

            try {
                Mail::raw($body, function ($message) use ($lead) {
                    $message->to($lead->email)->subject('Appointment confirmation');
                });
                $log->update(['status' => 'sent']);
            } catch (\Throwable $e) {
                Log::warning('Appointment email failed', ['error' => $e->getMessage()]);
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }
    }
}

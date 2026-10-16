<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Firm\FirmContext;
use App\Domain\Scheduling\FirmTime;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Scopes\FirmScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send 24 hour and 1 hour appointment reminders';

    public function handle(FirmTime $clock): int
    {
        $this->sendWindow('reminder_24h_sent_at', now()->addHours(23), now()->addHours(25), '24h', $clock);
        $this->sendWindow('reminder_1h_sent_at', now()->addMinutes(45), now()->addMinutes(75), '1h', $clock);

        return self::SUCCESS;
    }

    private function sendWindow(string $column, $from, $until, string $window, FirmTime $clock): void
    {
        $due = Appointment::query()
            ->withoutGlobalScope(FirmScope::class)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_at', [$from, $until])
            ->whereNull($column)
            ->get();

        foreach ($due as $appointment) {
            $claimed = Appointment::query()
                ->withoutGlobalScope(FirmScope::class)
                ->whereKey($appointment->id)
                ->whereNull($column)
                ->update([$column => now()]);

            if ($claimed !== 1) {
                continue;
            }

            app(FirmContext::class)->run((int) $appointment->firm_id, function () use ($appointment, $clock, $window): void {
                $appointment->load(['client', 'lead', 'lawyer.user', 'firm']);
                $email = $appointment->client?->email ?: $appointment->lead?->email;
                if (! $email) {
                    return;
                }

                Mail::to($email)->queue(new AppointmentReminderMail(
                    $clock->format($appointment->start_at),
                    $appointment->lawyer?->displayName() ?? 'your lawyer',
                    $appointment->firm?->firm_name ?? 'the firm',
                    $window,
                ));
            });
        }
    }
}

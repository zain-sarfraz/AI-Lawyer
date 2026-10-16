<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Firm\FirmContext;
use App\Mail\WaitlistOpeningMail;
use App\Models\Lawyer;
use App\Models\WaitlistEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

class WaitlistService
{
    public function __construct(private readonly FirmTime $clock) {}

    public function join(array $data): WaitlistEntry
    {
        return WaitlistEntry::query()->create([
            'lawyer_id' => $data['lawyer_id'],
            'appointment_type_id' => $data['appointment_type_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'range_start' => $data['range_start'],
            'range_end' => $data['range_end'],
            'status' => 'waiting',
        ]);
    }

    public function notifyFirst(Lawyer $lawyer, CarbonInterface $freedStartUtc): void
    {
        $localDate = $this->clock->toFirm($freedStartUtc)->toDateString();

        $entry = WaitlistEntry::query()
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'waiting')
            ->whereDate('range_start', '<=', $localDate)
            ->whereDate('range_end', '>=', $localDate)
            ->orderBy('id')
            ->first();

        if (! $entry || ! $entry->email) {
            return;
        }

        $claimed = WaitlistEntry::query()
            ->whereKey($entry->id)
            ->where('status', 'waiting')
            ->update([
                'status' => 'notified',
                'notified_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $entry->refresh();
        app(FirmContext::class)->run((int) $entry->firm_id, function () use ($entry, $freedStartUtc): void {
            $entry->load(['lawyer.user', 'firm']);
            $firm = $entry->firm;
            $lawyer = $entry->lawyer;
            $url = ($firm?->slug && $lawyer?->slug)
                ? url('/book/'.$firm->slug.'/'.$lawyer->slug)
                : url('/');

            Mail::to($entry->email)->queue(new WaitlistOpeningMail(
                $entry->name,
                $lawyer?->displayName() ?? 'your lawyer',
                $this->clock->format($freedStartUtc, 'M j, Y g:i A', $firm),
                $url,
            ));
        });
    }
}

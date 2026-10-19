<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Audit\AuditLogService;
use App\Models\Lawyer;
use App\Models\WorkingHour;
use Illuminate\Support\Facades\DB;

class LawyerAvailabilityService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * Replace a lawyer's weekly availability.
     *
     * @param  list<array{day_of_week: int, start_time: string, end_time: string, enabled?: bool}>  $days
     * @return list<WorkingHour>
     */
    public function syncWeekly(Lawyer $lawyer, array $days): array
    {
        return DB::transaction(function () use ($lawyer, $days) {
            $old = WorkingHour::query()
                ->where('lawyer_id', $lawyer->id)
                ->get()
                ->toArray();

            WorkingHour::query()->where('lawyer_id', $lawyer->id)->delete();

            $created = [];
            foreach ($days as $day) {
                $enabled = (bool) ($day['enabled'] ?? true);
                if (! $enabled) {
                    continue;
                }

                $dayOfWeek = (int) $day['day_of_week'];
                if ($dayOfWeek < 0 || $dayOfWeek > 6) {
                    continue;
                }

                $start = $this->normalizeTime((string) ($day['start_time'] ?? ''));
                $end = $this->normalizeTime((string) ($day['end_time'] ?? ''));
                if ($start === null || $end === null || $start >= $end) {
                    continue;
                }

                $created[] = WorkingHour::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $start,
                    'end_time' => $end,
                    'status' => 'active',
                ]);
            }

            $this->audit->log(
                'lawyer.availability.synced',
                $lawyer,
                $old,
                collect($created)->map->toArray()->all(),
            );

            return $created;
        });
    }

    /**
     * @return list<array{day_of_week: int, weekday: string, enabled: bool, start_time: string, end_time: string}>
     */
    public function weekFormState(Lawyer $lawyer): array
    {
        $labels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $byDay = WorkingHour::query()
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'active')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $rows = [];
        for ($d = 0; $d <= 6; $d++) {
            $first = $byDay->get($d)?->first();
            $rows[] = [
                'day_of_week' => $d,
                'weekday' => $labels[$d],
                'enabled' => (bool) $first,
                'start_time' => $first ? substr((string) $first->start_time, 0, 5) : '09:00',
                'end_time' => $first ? substr((string) $first->end_time, 0, 5) : '17:00',
            ];
        }

        return $rows;
    }

    private function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 5 ? $value.':00' : $value;
        }

        return null;
    }
}

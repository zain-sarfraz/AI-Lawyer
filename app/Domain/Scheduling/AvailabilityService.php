<?php

namespace App\Domain\Scheduling;

use App\Domain\Firm\FirmService;
use App\Enums\AvailabilityExceptionType;
use App\Enums\EntityStatus;
use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\FirmSetting;
use App\Models\Holiday;
use App\Models\Lawyer;
use App\Models\ScheduleBreak;
use App\Models\Service;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(private readonly FirmService $firmService) {}

    /**
     * @return list<array{start_at: string, end_at: string, lawyer_id: int, service_id: int, timezone: string}>
     */
    public function findSlots(
        Service $service,
        CarbonInterface $date,
        ?Lawyer $lawyer = null,
        bool $requireBookable = true,
    ): array {
        if ($requireBookable && (! $service->booking_enabled || $service->status !== EntityStatus::Active)) {
            return [];
        }

        $firm = $this->firmService->current();
        $lawyers = $lawyer
            ? collect([$lawyer])
            : $this->eligibleLawyers($service);

        $slots = [];
        foreach ($lawyers as $candidate) {
            $slots = array_merge($slots, $this->slotsForLawyer($service, $candidate, $date, $firm));
        }

        usort($slots, fn ($a, $b) => strcmp($a['start_at'], $b['start_at']));

        return $slots;
    }

    public function isSlotAvailable(
        Service $service,
        Lawyer $lawyer,
        CarbonInterface $startAt,
        ?int $ignoreAppointmentId = null,
    ): bool {
        $firm = $this->firmService->current();
        $timezone = $this->lawyerTimezone($lawyer, $firm);
        $localStart = Carbon::parse($startAt)->timezone($timezone);
        $duration = $service->duration_minutes;
        $buffer = $service->buffer_minutes ?? $firm->buffer_minutes;
        $endAt = $localStart->copy()->addMinutes($duration + $buffer);

        $windows = $this->availableWindowsForDate($lawyer, $localStart->toDateString(), $firm, $timezone);
        $fitsWindow = false;
        foreach ($windows as [$windowStart, $windowEnd]) {
            if ($localStart->gte($windowStart) && $endAt->lte($windowEnd)) {
                $fitsWindow = true;
                break;
            }
        }

        if (! $fitsWindow) {
            return false;
        }

        return ! $this->hasOverlap(
            $lawyer->id,
            $localStart->clone()->utc(),
            $endAt->clone()->utc(),
            $ignoreAppointmentId,
        );
    }

    /**
     * @return Collection<int, Lawyer>
     */
    public function eligibleLawyers(Service $service): Collection
    {
        return Lawyer::query()
            ->active()
            ->whereHas('services', fn ($q) => $q->where('services.id', $service->id))
            ->with('user')
            ->get();
    }

    /**
     * @return list<array{start_at: string, end_at: string, lawyer_id: int, service_id: int, timezone: string}>
     */
    private function slotsForLawyer(Service $service, Lawyer $lawyer, CarbonInterface $date, FirmSetting $firm): array
    {
        $timezone = $this->lawyerTimezone($lawyer, $firm);
        $localDate = Carbon::parse($date->toDateString(), $timezone);
        $windows = $this->availableWindowsForDate($lawyer, $localDate->toDateString(), $firm, $timezone);
        if ($windows === []) {
            return [];
        }

        $interval = max(5, (int) $firm->slot_interval_minutes);
        $duration = (int) $service->duration_minutes;
        $buffer = (int) ($service->buffer_minutes ?? $firm->buffer_minutes);
        $horizonEnd = now($timezone)->startOfDay()->addDays((int) $firm->booking_horizon_days)->endOfDay();

        if ($localDate->gt($horizonEnd)) {
            return [];
        }

        $blocking = Appointment::query()
            ->blocking()
            ->where('lawyer_id', $lawyer->id)
            ->where('start_at', '<', $localDate->copy()->endOfDay()->utc())
            ->where('end_at', '>', $localDate->copy()->startOfDay()->utc())
            ->get(['id', 'start_at', 'end_at']);

        $slots = [];
        foreach ($windows as [$windowStart, $windowEnd]) {
            $cursor = $windowStart->copy();
            // Align to interval grid from midnight
            $minutes = $cursor->hour * 60 + $cursor->minute;
            $mod = $minutes % $interval;
            if ($mod !== 0) {
                $cursor->addMinutes($interval - $mod);
            }

            while ($cursor->copy()->addMinutes($duration + $buffer)->lte($windowEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration + $buffer);
                if ($cursor->lt(now($timezone))) {
                    $cursor->addMinutes($interval);
                    continue;
                }

                $overlaps = $blocking->contains(function (Appointment $appointment) use ($cursor, $slotEnd) {
                    return $appointment->start_at->lt($slotEnd->copy()->utc())
                        && $appointment->end_at->gt($cursor->copy()->utc());
                });

                if (! $overlaps) {
                    $slots[] = [
                        'start_at' => $cursor->copy()->utc()->toIso8601String(),
                        'end_at' => $slotEnd->copy()->utc()->toIso8601String(),
                        'lawyer_id' => $lawyer->id,
                        'service_id' => $service->id,
                        'timezone' => $timezone,
                    ];
                }

                $cursor->addMinutes($interval);
            }
        }

        return $slots;
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function availableWindowsForDate(Lawyer $lawyer, string $date, FirmSetting $firm, string $timezone): array
    {
        $day = Carbon::parse($date, $timezone);
        if (Holiday::query()->whereDate('date', $day->toDateString())->where('status', EntityStatus::Active)->exists()) {
            return [];
        }

        $exceptions = AvailabilityException::query()
            ->where('lawyer_id', $lawyer->id)
            ->whereDate('date', $day->toDateString())
            ->get();

        if ($exceptions->contains(fn ($e) => $e->type === AvailabilityExceptionType::Unavailable && $e->start_time === null)) {
            return [];
        }

        $dayOfWeek = (int) $day->dayOfWeek; // 0 Sunday
        $hours = WorkingHour::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function ($q) use ($lawyer) {
                $q->where('lawyer_id', $lawyer->id)->orWhereNull('lawyer_id');
            })
            ->orderByRaw('lawyer_id is null') // prefer lawyer-specific
            ->get();

        $lawyerHours = $hours->where('lawyer_id', $lawyer->id);
        $hours = $lawyerHours->isNotEmpty() ? $lawyerHours : $hours->whereNull('lawyer_id');

        $windows = [];
        foreach ($hours as $hour) {
            $windows[] = [
                Carbon::parse($day->toDateString().' '.$hour->start_time, $timezone),
                Carbon::parse($day->toDateString().' '.$hour->end_time, $timezone),
            ];
        }

        // Open exceptions add availability
        foreach ($exceptions->where('type', AvailabilityExceptionType::Available) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $windows[] = [
                    Carbon::parse($day->toDateString().' '.$exception->start_time, $timezone),
                    Carbon::parse($day->toDateString().' '.$exception->end_time, $timezone),
                ];
            }
        }

        // Subtract breaks
        $breaks = ScheduleBreak::query()
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($lawyer) {
                $q->where('lawyer_id', $lawyer->id)->orWhereNull('lawyer_id');
            })
            ->get();

        foreach ($breaks as $break) {
            $windows = $this->subtractInterval(
                $windows,
                Carbon::parse($day->toDateString().' '.$break->start_time, $timezone),
                Carbon::parse($day->toDateString().' '.$break->end_time, $timezone),
            );
        }

        // Subtract partial unavailable exceptions
        foreach ($exceptions->where('type', AvailabilityExceptionType::Unavailable) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $windows = $this->subtractInterval(
                    $windows,
                    Carbon::parse($day->toDateString().' '.$exception->start_time, $timezone),
                    Carbon::parse($day->toDateString().' '.$exception->end_time, $timezone),
                );
            }
        }

        return array_values(array_filter($windows, fn ($w) => $w[0]->lt($w[1])));
    }

    /**
     * @param  list<array{0: Carbon, 1: Carbon}>  $windows
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function subtractInterval(array $windows, Carbon $cutStart, Carbon $cutEnd): array
    {
        $result = [];
        foreach ($windows as [$start, $end]) {
            if ($cutEnd->lte($start) || $cutStart->gte($end)) {
                $result[] = [$start, $end];
                continue;
            }
            if ($cutStart->gt($start)) {
                $result[] = [$start, $cutStart->copy()];
            }
            if ($cutEnd->lt($end)) {
                $result[] = [$cutEnd->copy(), $end];
            }
        }

        return $result;
    }

    private function hasOverlap(int $lawyerId, CarbonInterface $startUtc, CarbonInterface $endUtc, ?int $ignoreAppointmentId = null): bool
    {
        $query = Appointment::query()
            ->blocking()
            ->where('lawyer_id', $lawyerId)
            ->where('start_at', '<', $endUtc)
            ->where('end_at', '>', $startUtc);

        if ($ignoreAppointmentId) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        return $query->lockForUpdate()->exists();
    }

    public function lawyerTimezone(Lawyer $lawyer, ?FirmSetting $firm = null): string
    {
        $firm ??= $this->firmService->current();

        return $lawyer->timezone ?: ($firm->timezone ?: config('app.timezone', 'UTC'));
    }
}

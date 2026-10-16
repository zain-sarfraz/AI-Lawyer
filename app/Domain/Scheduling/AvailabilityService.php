<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Firm\FirmService;
use App\Enums\AvailabilityExceptionType;
use App\Enums\EntityStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AvailabilityException;
use App\Models\Firm;
use App\Models\Holiday;
use App\Models\Lawyer;
use App\Models\ScheduleBlock;
use App\Models\ScheduleBreak;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        private readonly FirmService $firmService,
        private readonly FirmTime $clock,
    ) {}

    /**
     * Bookable slots for a lawyer and appointment type across a firm-local date range.
     *
     * @return list<array<string, mixed>>
     */
    public function bookableSlots(
        Lawyer $lawyer,
        AppointmentType $type,
        CarbonInterface|string $rangeStart,
        CarbonInterface|string $rangeEnd,
    ): array {
        $firm = $this->firmService->current();
        $start = $this->clock->parseLocal($this->dateString($rangeStart), $firm)->startOfDay();
        $end = $this->clock->parseLocal($this->dateString($rangeEnd), $firm)->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        $slots = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $slots = array_merge($slots, $this->slotsOnDate(
                $lawyer,
                $day,
                $firm,
                (int) $type->duration_minutes,
                (int) $firm->buffer_before_minutes,
                (int) $firm->buffer_minutes,
                [
                    'appointment_type_id' => $type->id,
                    'service_id' => null,
                ],
            ));
        }

        return $slots;
    }

    /**
     * Service-based slots for one date. Kept so voice tools and the staff form keep working.
     *
     * @return list<array<string, mixed>>
     */
    public function findSlots(
        Service $service,
        CarbonInterface|string $date,
        ?Lawyer $lawyer = null,
        bool $requireBookable = true,
    ): array {
        if ($requireBookable && (! $service->booking_enabled || $service->status !== EntityStatus::Active)) {
            return [];
        }

        $firm = $this->firmService->current();
        $lawyers = $lawyer ? collect([$lawyer]) : $this->eligibleLawyers($service);
        $localDate = $this->clock->parseLocal($this->dateString($date), $firm)->startOfDay();
        $bufferAfter = (int) ($service->buffer_minutes ?? $firm->buffer_minutes);

        $slots = [];
        foreach ($lawyers as $candidate) {
            $slots = array_merge($slots, $this->slotsOnDate(
                $candidate,
                $localDate,
                $firm,
                (int) $service->duration_minutes,
                (int) $firm->buffer_before_minutes,
                $bufferAfter,
                [
                    'appointment_type_id' => null,
                    'service_id' => $service->id,
                ],
            ));
        }

        usort($slots, fn (array $a, array $b) => strcmp((string) $a['start_at'], (string) $b['start_at']));

        return $slots;
    }

    public function isSlotAvailable(
        Service|AppointmentType $bookable,
        Lawyer $lawyer,
        CarbonInterface|string $startAt,
        ?int $ignoreAppointmentId = null,
    ): bool {
        $firm = $this->firmService->current();
        $timezone = $this->clock->timezone($firm);
        $localStart = $this->clock->parseLocal(
            $startAt instanceof CarbonInterface ? $startAt->toIso8601String() : $startAt,
            $firm,
        );
        $duration = (int) $bookable->duration_minutes;
        $bufferBefore = (int) $firm->buffer_before_minutes;
        $bufferAfter = $bookable instanceof Service
            ? (int) ($bookable->buffer_minutes ?? $firm->buffer_minutes)
            : (int) $firm->buffer_minutes;
        $localEnd = $localStart->copy()->addMinutes($duration);

        if (! $this->fitsWindow($lawyer, $localStart, $localEnd, $firm, $timezone)) {
            return false;
        }

        if ($localStart->lt($this->clock->now($firm)->addMinutes((int) $firm->min_notice_minutes))) {
            return false;
        }

        $horizonEnd = $this->clock->now($firm)->startOfDay()->addDays((int) $firm->booking_horizon_days)->endOfDay();
        if ($localStart->gt($horizonEnd)) {
            return false;
        }

        return ! $this->hasOverlap(
            $lawyer->id,
            $localStart,
            $localEnd,
            $bufferBefore,
            $bufferAfter,
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

    public function lawyerTimezone(Lawyer $lawyer, ?Firm $firm = null): string
    {
        return $this->clock->timezone($firm ?? $this->firmService->current());
    }

    /**
     * @param  array{appointment_type_id: int|null, service_id: int|null}  $meta
     * @return list<array<string, mixed>>
     */
    private function slotsOnDate(
        Lawyer $lawyer,
        CarbonInterface $localDate,
        Firm $firm,
        int $duration,
        int $bufferBefore,
        int $bufferAfter,
        array $meta,
    ): array {
        $timezone = $this->clock->timezone($firm);
        $day = Carbon::parse($localDate->toDateString(), $timezone)->startOfDay();
        $horizonEnd = $this->clock->now($firm)->startOfDay()->addDays((int) $firm->booking_horizon_days)->endOfDay();

        if ($day->gt($horizonEnd)) {
            return [];
        }

        $windows = $this->availableWindowsForDate($lawyer, $day->toDateString(), $firm, $timezone);
        if ($windows === []) {
            return [];
        }

        $interval = max(5, (int) $firm->slot_interval_minutes);
        $earliest = $this->clock->now($firm)->addMinutes((int) $firm->min_notice_minutes);
        $blocking = $this->blockingAppointments($lawyer->id, $day, $bufferBefore, $bufferAfter);

        $slots = [];
        foreach ($windows as [$windowStart, $windowEnd]) {
            $cursor = $windowStart->copy();
            $minutes = ($cursor->hour * 60) + $cursor->minute;
            $mod = $minutes % $interval;
            if ($mod !== 0) {
                $cursor->addMinutes($interval - $mod);
            }

            while ($cursor->copy()->addMinutes($duration)->lte($windowEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($cursor->lt($earliest) || $this->overlapsBuffered($blocking, $cursor, $slotEnd, $bufferBefore, $bufferAfter)) {
                    $cursor->addMinutes($interval);

                    continue;
                }

                $startUtc = $cursor->copy()->utc();
                $endUtc = $slotEnd->copy()->utc();
                $slots[] = [
                    'start_at' => $startUtc->toIso8601String(),
                    'end_at' => $endUtc->toIso8601String(),
                    'start_local' => $cursor->format('Y-m-d H:i'),
                    'end_local' => $slotEnd->format('Y-m-d H:i'),
                    'label' => $cursor->format('g:i A'),
                    'lawyer_id' => $lawyer->id,
                    'service_id' => $meta['service_id'],
                    'appointment_type_id' => $meta['appointment_type_id'],
                    'timezone' => $timezone,
                ];

                $cursor->addMinutes($interval);
            }
        }

        return $slots;
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function availableWindowsForDate(Lawyer $lawyer, string $date, Firm $firm, string $timezone): array
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

        $dayOfWeek = (int) $day->dayOfWeek;

        // Lawyers are available the whole day for booking. The exact slot is decided on the call.
        // Holidays, breaks, blocks, and existing appointment overlaps are still applied below.
        $windows = [[
            $this->atLocalTime($day, '00:00:00', $timezone),
            $this->atLocalTime($day, '23:59:00', $timezone),
        ]];

        foreach ($exceptions->where('type', AvailabilityExceptionType::Available) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $windows[] = [
                    $this->atLocalTime($day, (string) $exception->start_time, $timezone),
                    $this->atLocalTime($day, (string) $exception->end_time, $timezone),
                ];
            }
        }

        $breaks = ScheduleBreak::query()
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($lawyer) {
                $q->where('lawyer_id', $lawyer->id)->orWhereNull('lawyer_id');
            })
            ->get();

        foreach ($breaks as $break) {
            $windows = $this->subtractInterval(
                $windows,
                $this->atLocalTime($day, (string) $break->start_time, $timezone),
                $this->atLocalTime($day, (string) $break->end_time, $timezone),
            );
        }

        foreach ($exceptions->where('type', AvailabilityExceptionType::Unavailable) as $exception) {
            if ($exception->start_time && $exception->end_time) {
                $windows = $this->subtractInterval(
                    $windows,
                    $this->atLocalTime($day, (string) $exception->start_time, $timezone),
                    $this->atLocalTime($day, (string) $exception->end_time, $timezone),
                );
            }
        }

        foreach ($this->blocksOnDate($lawyer, $day, $timezone) as [$cutStart, $cutEnd]) {
            $windows = $this->subtractInterval($windows, $cutStart, $cutEnd);
        }

        return array_values(array_filter($windows, fn (array $window) => $window[0]->lt($window[1])));
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function blocksOnDate(Lawyer $lawyer, Carbon $day, string $timezone): array
    {
        $blocks = ScheduleBlock::query()->where('lawyer_id', $lawyer->id)->get();
        $cuts = [];

        foreach ($blocks as $block) {
            if ($block->recurrence === 'weekly') {
                if ((int) $block->day_of_week !== (int) $day->dayOfWeek) {
                    continue;
                }
                if ($block->starts_on && $day->toDateString() < $block->starts_on->toDateString()) {
                    continue;
                }
                if ($block->ends_on && $day->toDateString() > $block->ends_on->toDateString()) {
                    continue;
                }
                if ($block->start_time && $block->end_time) {
                    $cuts[] = [
                        $this->atLocalTime($day, (string) $block->start_time, $timezone),
                        $this->atLocalTime($day, (string) $block->end_time, $timezone),
                    ];
                }

                continue;
            }

            if ($block->starts_at && $block->ends_at) {
                $start = $this->clock->toFirm($block->starts_at);
                $end = $this->clock->toFirm($block->ends_at);
                if ($end->toDateString() < $day->toDateString() || $start->toDateString() > $day->toDateString()) {
                    continue;
                }
                $cuts[] = [
                    $start->toDateString() === $day->toDateString() ? $start : $day->copy()->startOfDay(),
                    $end->toDateString() === $day->toDateString() ? $end : $day->copy()->endOfDay(),
                ];
            }
        }

        return $cuts;
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

    private function fitsWindow(Lawyer $lawyer, Carbon $localStart, Carbon $localEnd, Firm $firm, string $timezone): bool
    {
        foreach ($this->availableWindowsForDate($lawyer, $localStart->toDateString(), $firm, $timezone) as [$windowStart, $windowEnd]) {
            if ($localStart->gte($windowStart) && $localEnd->lte($windowEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function blockingAppointments(int $lawyerId, Carbon $day, int $bufferBefore, int $bufferAfter): Collection
    {
        return Appointment::query()
            ->blocking()
            ->where('lawyer_id', $lawyerId)
            ->where('start_at', '<', $day->copy()->endOfDay()->addMinutes($bufferBefore)->utc())
            ->where('end_at', '>', $day->copy()->startOfDay()->subMinutes($bufferAfter)->utc())
            ->get(['id', 'start_at', 'end_at']);
    }

    /**
     * @param  Collection<int, Appointment>  $blocking
     */
    private function overlapsBuffered(
        Collection $blocking,
        Carbon $localStart,
        Carbon $localEnd,
        int $bufferBefore,
        int $bufferAfter,
    ): bool {
        return $blocking->contains(function (Appointment $appointment) use ($localStart, $localEnd, $bufferBefore, $bufferAfter) {
            $occupiedStart = $appointment->start_at->copy()->subMinutes($bufferBefore);
            $occupiedEnd = $appointment->end_at->copy()->addMinutes($bufferAfter);

            return $localStart->copy()->utc()->lt($occupiedEnd)
                && $localEnd->copy()->utc()->gt($occupiedStart);
        });
    }

    private function hasOverlap(
        int $lawyerId,
        Carbon $localStart,
        Carbon $localEnd,
        int $bufferBefore,
        int $bufferAfter,
        ?int $ignoreAppointmentId = null,
    ): bool {
        $rangeStart = $localStart->copy()->subMinutes($bufferBefore)->utc();
        $rangeEnd = $localEnd->copy()->addMinutes($bufferAfter)->utc();

        $query = Appointment::query()
            ->blocking()
            ->where('lawyer_id', $lawyerId)
            ->where('start_at', '<', $rangeEnd)
            ->where('end_at', '>', $rangeStart);

        if ($ignoreAppointmentId) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        $rows = $query->lockForUpdate()->get(['id', 'start_at', 'end_at']);

        return $this->overlapsBuffered($rows, $localStart, $localEnd, $bufferBefore, $bufferAfter);
    }

    private function atLocalTime(Carbon $day, string $time, string $timezone): Carbon
    {
        $clock = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::parse($day->toDateString().' '.$clock, $timezone);
    }

    private function dateString(CarbonInterface|string $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return substr($value, 0, 10);
    }
}

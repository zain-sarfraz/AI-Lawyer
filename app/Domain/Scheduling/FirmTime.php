<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\Firm;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The only place appointment instants are converted.
 * Storage is UTC. Display and input use Firm::resolvedTimezone().
 */
class FirmTime
{
    public function timezone(?Firm $firm = null): string
    {
        $firm ??= Firm::current();

        return $firm->resolvedTimezone();
    }

    public function now(?Firm $firm = null): Carbon
    {
        return Carbon::now($this->timezone($firm));
    }

    public function parseLocal(CarbonInterface|string $value, ?Firm $firm = null): Carbon
    {
        $timezone = $this->timezone($firm);

        if ($value instanceof CarbonInterface) {
            return $value->copy()->timezone($timezone);
        }

        if ($this->hasOffset($value)) {
            return Carbon::parse($value)->timezone($timezone);
        }

        return Carbon::parse($value, $timezone);
    }

    public function toUtc(CarbonInterface|string $value, ?Firm $firm = null): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy()->utc();
        }

        if ($this->hasOffset($value)) {
            return Carbon::parse($value)->utc();
        }

        return Carbon::parse($value, $this->timezone($firm))->utc();
    }

    public function toFirm(CarbonInterface|string $utc, ?Firm $firm = null): Carbon
    {
        return Carbon::parse($utc)->timezone($this->timezone($firm));
    }

    public function format(CarbonInterface|string|null $utc, string $format = 'M j, Y g:i A', ?Firm $firm = null): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }

        return $this->toFirm($utc, $firm)->format($format);
    }

    /**
     * UTC bounds [start, end) for a firm-local calendar date.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function utcDayBounds(string $date, ?Firm $firm = null): array
    {
        $start = Carbon::parse(substr($date, 0, 10), $this->timezone($firm))->startOfDay();

        return [$start->copy()->utc(), $start->copy()->addDay()->utc()];
    }

    private function hasOffset(string $value): bool
    {
        return (bool) preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/', trim($value));
    }
}

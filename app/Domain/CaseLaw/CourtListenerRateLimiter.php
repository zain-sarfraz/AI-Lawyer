<?php

namespace App\Domain\CaseLaw;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CourtListenerRateLimiter
{
    public function assertCanRequest(): void
    {
        if ($this->remaining('day') <= 0) {
            throw new RuntimeException('CourtListener daily budget exhausted. Resume later from saved cursor.');
        }
        if ($this->remaining('hour') <= 0) {
            throw new RuntimeException('CourtListener hourly budget exhausted.');
        }
        if ($this->remaining('minute') <= 0) {
            $this->sleepUntilWindow('minute');
        }
    }

    public function recordSuccess(): void
    {
        $this->bump('minute');
        $this->bump('hour');
        $this->bump('day');
        Log::info('courtlistener.budget', [
            'minute' => $this->remaining('minute'),
            'hour' => $this->remaining('hour'),
            'day' => $this->remaining('day'),
        ]);
    }

    public function honorRetryAfter(?int $seconds): void
    {
        $wait = max(1, $seconds ?? (int) config('case_law.courtlistener.retry_after_default', 60));
        Log::warning('courtlistener.rate_limited', ['sleep_seconds' => $wait]);
        sleep($wait);
    }

    public function remaining(string $window): int
    {
        $max = $this->maxFor($window);
        $used = (int) Cache::get($this->key($window), 0);

        return max(0, $max - $used);
    }

    private function bump(string $window): void
    {
        $key = $this->key($window);
        $ttl = match ($window) {
            'minute' => now()->addMinute(),
            'hour' => now()->addHour(),
            default => now()->addDay(),
        };
        if (! Cache::has($key)) {
            Cache::put($key, 1, $ttl);

            return;
        }
        Cache::increment($key);
    }

    private function sleepUntilWindow(string $window): void
    {
        usleep(match ($window) {
            'minute' => 1_000_000,
            default => 5_000_000,
        });
    }

    private function maxFor(string $window): int
    {
        return match ($window) {
            'minute' => (int) config('case_law.courtlistener.max_per_minute', 5),
            'hour' => (int) config('case_law.courtlistener.max_per_hour', 100),
            default => (int) config('case_law.courtlistener.max_per_day', 500),
        };
    }

    private function key(string $window): string
    {
        return 'courtlistener:budget:'.$window.':'.now()->format(match ($window) {
            'minute' => 'YmdHi',
            'hour' => 'YmdH',
            default => 'Ymd',
        });
    }
}

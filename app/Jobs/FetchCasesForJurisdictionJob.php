<?php

namespace App\Jobs;

use App\Domain\CaseLaw\CaseLawUpserter;
use App\Domain\CaseLaw\CaseSourceRegistry;
use App\Models\Jurisdiction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FetchCasesForJurisdictionJob implements ShouldQueue
{
    use Queueable;

    /** @param  array<string, mixed>  $options */
    public function __construct(
        public readonly string $jurisdictionCode,
        public readonly int $limit = 25,
        public readonly array $options = [],
    ) {}

    public function handle(CaseSourceRegistry $registry, CaseLawUpserter $upserter): void
    {
        $jurisdiction = Jurisdiction::query()->where('code', $this->jurisdictionCode)->first();
        if (! $jurisdiction) {
            Log::warning('case_law.fetch.missing_jurisdiction', ['code' => $this->jurisdictionCode]);

            return;
        }

        $adapter = $registry->forCode($jurisdiction->code);
        $configuredMax = (int) config('case_law.fetch.max_requests_per_run', 25);
        // limit <= 0 means unlimited (used by cases:fetch --all).
        $unlimited = $this->limit <= 0;
        $max = $unlimited ? 0 : ($this->limit > 0 ? $this->limit : $configuredMax);
        $delayMs = (int) config('case_law.fetch.delay_ms', 2500);
        $codeUpper = strtoupper($jurisdiction->code);
        // Bulk RSS / US listing already rate-limits at the HTTP layer — skip per-row sleeps.
        if (str_starts_with($codeUpper, 'US-') || str_starts_with($codeUpper, 'AU-')) {
            $delayMs = 0;
        }
        $count = 0;
        $failed = 0;
        $skipped = 0;
        $processed = 0;

        $jurisdiction->update([
            'last_case_fetch_at' => now(),
            'last_case_fetch_status' => 'running',
            'last_case_fetch_message' => null,
        ]);

        try {
            foreach ($adapter->search(array_merge([
                'limit' => $max,
                'jurisdiction_code' => $jurisdiction->code,
                // US listing uses search-only mapping (fast). Set enrich=true for full text/AI.
                'enrich' => false,
                'enrich_counsel' => str_starts_with($codeUpper, 'GB-'),
            ], $this->options)) as $row) {
                if (! $unlimited && $processed >= $max) {
                    break;
                }
                try {
                    $before = \App\Models\CaseLawRecord::query()
                        ->where('source', $row['source'] ?? '')
                        ->where('external_id', $row['external_id'] ?? '')
                        ->exists();
                    $upserter->upsert($jurisdiction, $row);
                    if ($before) {
                        $skipped++;
                    } else {
                        $count++;
                    }
                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::error('case_law.fetch.upsert_failed', [
                        'code' => $jurisdiction->code,
                        'external_id' => $row['external_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            $jurisdiction->update([
                'last_case_fetch_at' => now(),
                'last_case_fetch_status' => 'ok',
                'last_case_fetch_message' => "upserted={$count}; skipped={$skipped}; failed={$failed}",
            ]);
        } catch (RuntimeException $e) {
            // Budget / rate-limit stop — keep resume cursor; mark status for UI.
            $jurisdiction->update([
                'last_case_fetch_at' => now(),
                'last_case_fetch_status' => 'paused',
                'last_case_fetch_message' => $e->getMessage(),
            ]);
            Log::warning('case_law.fetch.paused', [
                'code' => $jurisdiction->code,
                'error' => $e->getMessage(),
                'upserted' => $count,
            ]);

            return;
        } catch (Throwable $e) {
            $jurisdiction->update([
                'last_case_fetch_at' => now(),
                'last_case_fetch_status' => 'failed',
                'last_case_fetch_message' => $e->getMessage(),
            ]);
            Log::error('case_law.fetch.adapter_failed', [
                'code' => $jurisdiction->code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('case_law.fetch.completed', [
            'code' => $jurisdiction->code,
            'upserted' => $count,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);
    }
}

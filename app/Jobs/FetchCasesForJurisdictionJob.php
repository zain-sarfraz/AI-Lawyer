<?php

namespace App\Jobs;

use App\Domain\CaseLaw\CaseLawUpserter;
use App\Domain\CaseLaw\CaseSourceRegistry;
use App\Models\Jurisdiction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchCasesForJurisdictionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $jurisdictionCode,
        public readonly int $limit = 25,
    ) {}

    public function handle(CaseSourceRegistry $registry, CaseLawUpserter $upserter): void
    {
        $jurisdiction = Jurisdiction::query()->where('code', $this->jurisdictionCode)->first();
        if (! $jurisdiction) {
            Log::warning('case_law.fetch.missing_jurisdiction', ['code' => $this->jurisdictionCode]);

            return;
        }

        $adapter = $registry->forCode($jurisdiction->code);
        // Honour the explicit command/job limit; config is only the default when limit is omitted.
        $configuredMax = (int) config('case_law.fetch.max_requests_per_run', 25);
        $max = $this->limit > 0 ? $this->limit : $configuredMax;
        $delayMs = (int) config('case_law.fetch.delay_ms', 2500);
        $count = 0;

        try {
            foreach ($adapter->search([
                'limit' => $max,
                'jurisdiction_code' => $jurisdiction->code,
                'enrich' => false,
                // UK Atom listings omit counsel; pull from judgment XML when fetching GB-EAW.
                'enrich_counsel' => str_starts_with(strtoupper($jurisdiction->code), 'GB-'),
            ]) as $row) {
                if ($count >= $max) {
                    break;
                }
                try {
                    $upserter->upsert($jurisdiction, $row);
                    $count++;
                } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            Log::error('case_law.fetch.adapter_failed', [
                'code' => $jurisdiction->code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('case_law.fetch.completed', ['code' => $jurisdiction->code, 'upserted' => $count]);
    }
}

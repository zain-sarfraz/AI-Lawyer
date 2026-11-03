<?php

namespace App\Console\Commands;

use App\Jobs\FetchCasesForJurisdictionJob;
use App\Models\CaseLawRecord;
use App\Models\Jurisdiction;
use Illuminate\Console\Command;

class FetchCasesCommand extends Command
{
    protected $signature = 'cases:fetch
        {jurisdiction_code : e.g. US-FED, PK-SD, GB-EAW}
        {--limit= : Max opinions to upsert (default from config; US-FED default 2000)}
        {--court= : CourtListener court id (default scotus for US)}
        {--from= : filed_after YYYY-MM-DD}
        {--to= : filed_before YYYY-MM-DD}
        {--enrich : Also download full opinion text (slower; needed for AI)}
        {--reset-cursor : Ignore saved CourtListener resume cursor}
        {--sync : Run inline instead of queueing}';

    protected $description = 'Fetch case law for a jurisdiction code via its registered adapter';

    public function handle(): int
    {
        $code = strtoupper((string) $this->argument('jurisdiction_code'));
        $isUs = str_starts_with($code, 'US-');
        $limitOpt = $this->option('limit');
        $limit = $limitOpt !== null && $limitOpt !== ''
            ? max(1, (int) $limitOpt)
            : ($isUs ? 2000 : (int) config('case_law.fetch.max_requests_per_run', 25));

        if (! Jurisdiction::query()->where('code', $code)->exists()) {
            $this->error("Unknown jurisdiction code: {$code}");

            return self::FAILURE;
        }

        $court = $this->option('court') ?: ($isUs ? (string) config('case_law.courtlistener.default_court', 'scotus') : null);
        $from = $this->option('from') ?: ($isUs ? now()->subYears(3)->toDateString() : null);
        $to = $this->option('to') ?: ($isUs ? now()->toDateString() : null);

        if ($isUs && $this->option('reset-cursor')) {
            $cursorKey = 'courtlistener:cursor:'.$code.':'.$court.':'.$from.':'.$to;
            \Illuminate\Support\Facades\Cache::forget($cursorKey);
            $this->warn("Cleared resume cursor: {$cursorKey}");
        }

        $options = array_filter([
            'court' => $court,
            'from' => $from,
            'to' => $to,
            'enrich' => $this->option('enrich') ? true : ($isUs ? false : null),
        ], static fn ($v) => $v !== null && $v !== '');

        $before = CaseLawRecord::query()->whereHas('jurisdiction', fn ($q) => $q->where('code', $code))->count();

        $job = new FetchCasesForJurisdictionJob($code, $limit, $options);
        if ($this->option('sync') || $isUs) {
            // US runs sync by default so the artisan summary is accurate.
            try {
                dispatch_sync($job);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $after = CaseLawRecord::query()->whereHas('jurisdiction', fn ($q) => $q->where('code', $code))->count();
            $j = Jurisdiction::query()->where('code', $code)->first();
            $this->info("Fetched (sync) for {$code} (limit {$limit}).");
            $this->line('  Options: '.json_encode($options));
            $this->line('  Net new rows: '.max(0, $after - $before));
            $this->line('  Status: '.($j?->last_case_fetch_status ?? 'n/a'));
            $this->line('  Message: '.($j?->last_case_fetch_message ?? ''));
        } else {
            dispatch($job);
            $this->info("Queued FetchCasesForJurisdictionJob for {$code} (limit {$limit}).");
        }

        return self::SUCCESS;
    }
}

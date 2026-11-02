<?php

namespace App\Console\Commands;

use App\Jobs\FetchCasesForJurisdictionJob;
use App\Models\Jurisdiction;
use Illuminate\Console\Command;

class FetchCasesCommand extends Command
{
    protected $signature = 'cases:fetch {jurisdiction_code} {--limit=25} {--sync : Run inline instead of queueing}';

    protected $description = 'Fetch case law for a jurisdiction code (e.g. PK-SD, GB-EAW) via its registered adapter';

    public function handle(): int
    {
        $code = strtoupper((string) $this->argument('jurisdiction_code'));
        $limit = max(1, (int) $this->option('limit'));

        if (! Jurisdiction::query()->where('code', $code)->exists()) {
            $this->error("Unknown jurisdiction code: {$code}");

            return self::FAILURE;
        }

        $job = new FetchCasesForJurisdictionJob($code, $limit);
        if ($this->option('sync')) {
            dispatch_sync($job);
            $this->info("Fetched (sync) for {$code} (limit {$limit}).");
        } else {
            dispatch($job);
            $this->info("Queued FetchCasesForJurisdictionJob for {$code} (limit {$limit}).");
        }

        return self::SUCCESS;
    }
}

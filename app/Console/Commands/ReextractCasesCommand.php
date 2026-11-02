<?php

namespace App\Console\Commands;

use App\Jobs\ExtractCaseInsightsJob;
use App\Models\CaseLawRecord;
use Illuminate\Console\Command;

class ReextractCasesCommand extends Command
{
    protected $signature = 'cases:reextract
        {--only-demo : Limit to is_demo records}
        {--ids= : Comma-separated case_law_records ids}
        {--limit= : Max records to queue}
        {--sync : Run inline instead of queueing}';

    protected $description = 'Re-run AI extraction for case law records that have full_text (does not delete existing data)';

    public function handle(): int
    {
        $query = CaseLawRecord::query()
            ->whereNotNull('full_text')
            ->where('full_text', '!=', '');

        if ($this->option('only-demo')) {
            $query->where('is_demo', true);
        }

        if ($ids = trim((string) $this->option('ids'))) {
            $query->whereIn('id', array_filter(array_map('intval', explode(',', $ids))));
        }

        if ($this->option('limit') !== null) {
            $query->limit(max(1, (int) $this->option('limit')));
        }

        $records = $query->orderBy('id')->get(['id', 'title']);
        if ($records->isEmpty()) {
            $this->warn('No matching records with full_text.');

            return self::SUCCESS;
        }

        foreach ($records as $record) {
            $job = new ExtractCaseInsightsJob((int) $record->id);
            if ($this->option('sync')) {
                dispatch_sync($job);
                $this->line("Re-extracted #{$record->id} {$record->title}");
            } else {
                dispatch($job);
                $this->line("Queued #{$record->id} {$record->title}");
            }
        }

        $this->info('Done: '.$records->count().' record(s).');

        return self::SUCCESS;
    }
}

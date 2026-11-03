<?php

namespace App\Console\Commands;

use App\Models\CaseLawRecord;
use Illuminate\Console\Command;

class PurgeDemoCasesCommand extends Command
{
    protected $signature = 'cases:purge-demo
        {--force : Actually delete matching rows (default is dry-run)}';

    protected $description = 'Purge demo/sample case_law_records (dry-run unless --force)';

    public function handle(): int
    {
        $query = CaseLawRecord::query()->where(function ($q) {
            $q->where('is_demo', true)
                ->orWhere('source', 'demo')
                ->orWhereNull('source')
                ->orWhere('source', '')
                ->orWhereNull('external_id')
                ->orWhere('external_id', '')
                ->orWhereNull('judgment_url')
                ->orWhere('judgment_url', '')
                ->orWhere(function ($inner) {
                    $inner->whereNotNull('judgment_url')
                        ->where('judgment_url', 'not like', 'http://%')
                        ->where('judgment_url', 'not like', 'https://%');
                });
        });

        $count = (clone $query)->count();
        $bySource = (clone $query)
            ->selectRaw('COALESCE(source, "(null)") as src, count(*) as c')
            ->groupBy('src')
            ->pluck('c', 'src');

        $this->info('Matching rows: '.$count);
        foreach ($bySource as $src => $c) {
            $this->line("  {$src}: {$c}");
        }

        if (! $this->option('force')) {
            $this->warn('Dry-run only. Re-run with --force to delete.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} rows.");

        return self::SUCCESS;
    }
}
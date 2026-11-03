<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Intentionally empty / local-only. Never seed fictional judgments into production UI.
 * Run manually only when APP_ENV=local if you need throwaway fixtures for layout work.
 */
class DemoCasesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoCasesSeeder refused: APP_ENV is not local.');

            return;
        }

        $this->command?->warn('DemoCasesSeeder is a no-op. Add local fixtures here only if needed for UI work.');
    }
}
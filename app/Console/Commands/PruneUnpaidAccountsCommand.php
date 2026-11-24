<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneUnpaidAccountsCommand extends Command
{
    protected $signature = 'users:prune-unpaid {--days=7 : Number of days an unpaid account may stay pending}';

    protected $description = 'Delete unpaid accounts pending payment for longer than 7 days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $unpaidUsers = User::withoutGlobalScopes()
            ->where('status', UserStatus::PendingPayment)
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;

        foreach ($unpaidUsers as $user) {
            DB::transaction(function () use ($user, &$count) {
                // If this user owns a pending firm with no active subscriptions, clean up the firm
                if ($user->firm_id) {
                    $firm = Firm::withoutGlobalScopes()->find($user->firm_id);
                    if ($firm && $firm->status === 'pending' && $firm->users()->count() <= 1) {
                        $firm->delete();
                    }
                }

                $user->delete();
                $count++;
            });
        }

        $this->info("Pruned {$count} unpaid pending accounts older than {$days} days.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduleBreak;

class ScheduleBreakPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'schedules.manage';
    }

    protected function managePermission(): string
    {
        return 'schedules.manage';
    }
}

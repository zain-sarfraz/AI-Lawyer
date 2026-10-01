<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AvailabilityException;

class AvailabilityExceptionPolicy extends TenantPolicy
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

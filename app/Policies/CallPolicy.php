<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Call;

class CallPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'calls.view';
    }

    protected function managePermission(): string
    {
        return 'calls.manage';
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'users.manage';
    }

    protected function managePermission(): string
    {
        return 'users.manage';
    }
}

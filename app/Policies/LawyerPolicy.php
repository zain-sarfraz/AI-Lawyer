<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lawyer;

class LawyerPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'lawyers.manage';
    }

    protected function managePermission(): string
    {
        return 'lawyers.manage';
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;

class ServicePolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'services.manage';
    }

    protected function managePermission(): string
    {
        return 'services.manage';
    }
}

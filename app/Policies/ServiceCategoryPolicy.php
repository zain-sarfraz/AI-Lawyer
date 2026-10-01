<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceCategory;

class ServiceCategoryPolicy extends TenantPolicy
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

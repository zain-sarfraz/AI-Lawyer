<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Integration;

class IntegrationPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'integrations.manage';
    }

    protected function managePermission(): string
    {
        return 'integrations.manage';
    }
}

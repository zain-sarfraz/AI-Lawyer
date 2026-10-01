<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;

class LeadPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'leads.view';
    }

    protected function managePermission(): string
    {
        return 'leads.manage';
    }
}

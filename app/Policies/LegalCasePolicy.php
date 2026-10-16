<?php

declare(strict_types=1);

namespace App\Policies;

class LegalCasePolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'cases.view';
    }

    protected function managePermission(): string
    {
        return 'cases.manage';
    }
}

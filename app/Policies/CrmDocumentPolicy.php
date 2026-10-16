<?php

declare(strict_types=1);

namespace App\Policies;

class CrmDocumentPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'documents.view';
    }

    protected function managePermission(): string
    {
        return 'documents.manage';
    }
}

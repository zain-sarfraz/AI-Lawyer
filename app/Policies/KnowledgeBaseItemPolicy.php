<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeBaseItem;

class KnowledgeBaseItemPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'knowledge.manage';
    }

    protected function managePermission(): string
    {
        return 'knowledge.manage';
    }
}

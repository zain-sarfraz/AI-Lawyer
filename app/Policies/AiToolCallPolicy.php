<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiToolCall;

class AiToolCallPolicy extends TenantPolicy
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

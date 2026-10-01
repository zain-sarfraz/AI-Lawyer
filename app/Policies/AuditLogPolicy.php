<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'audit.view';
    }

    protected function managePermission(): string
    {
        return 'audit.view';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }
}

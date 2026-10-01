<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClientPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'clients.view';
    }

    protected function managePermission(): string
    {
        return 'clients.manage';
    }

    protected function lawyerOwns(User $user, Model $model): bool
    {
        $restrictedId = $this->restrictedLawyerId($user);
        if ($restrictedId === null) {
            return true;
        }

        if (! $model instanceof Client) {
            return false;
        }

        return $model->appointments()->where('lawyer_id', $restrictedId)->exists()
            || $model->leads()->where('lawyer_id', $restrictedId)->exists();
    }
}

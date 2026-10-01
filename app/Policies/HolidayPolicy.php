<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HolidayPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'schedules.manage';
    }

    protected function managePermission(): string
    {
        return 'schedules.manage';
    }

    public function create(User $user): bool
    {
        return parent::create($user) && $this->restrictedLawyerId($user) === null;
    }

    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model) && $this->restrictedLawyerId($user) === null;
    }
}

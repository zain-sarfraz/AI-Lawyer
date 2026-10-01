<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lawyer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class TenantPolicy
{
    abstract protected function viewPermission(): string;

    abstract protected function managePermission(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewPermission()) || $user->can($this->managePermission());
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user) && $this->sameFirm($user, $model) && $this->lawyerOwns($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->managePermission());
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->managePermission()) && $this->sameFirm($user, $model) && $this->lawyerOwns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    protected function sameFirm(User $user, Model $model): bool
    {
        return (int) $model->getAttribute('firm_id') === (int) $user->firm_id;
    }

    protected function lawyerOwns(User $user, Model $model): bool
    {
        $restrictedId = $this->restrictedLawyerId($user);
        if ($restrictedId === null) {
            return true;
        }

        if ($model instanceof Lawyer) {
            return (int) $model->getKey() === $restrictedId;
        }

        if (! array_key_exists('lawyer_id', $model->getAttributes())) {
            return true;
        }

        return (int) $model->getAttribute('lawyer_id') === $restrictedId;
    }

    protected function restrictedLawyerId(User $user): ?int
    {
        if (! $user->hasRole('Lawyer') || $user->hasAnyRole(['Owner', 'Admin'])) {
            return null;
        }

        $id = Lawyer::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('firm_id', $user->firm_id)
            ->value('id');

        return $id ? (int) $id : null;
    }
}

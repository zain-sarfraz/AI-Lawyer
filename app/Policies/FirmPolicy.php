<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Firm;
use App\Models\User;

class FirmPolicy
{
    public function view(User $user, Firm $firm): bool
    {
        $hasPermission = $user->can('firm.settings.manage') || $user->can('ai.settings.manage');
        $isOwner = (int) $firm->owner_id === (int) $user->id;
        $isSameFirm = (int) $user->firm_id === (int) $firm->id;

        return $hasPermission && ($isSameFirm || $isOwner);
    }

    public function update(User $user, Firm $firm): bool
    {
        return $this->view($user, $firm);
    }
}

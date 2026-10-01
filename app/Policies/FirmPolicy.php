<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Firm;
use App\Models\User;

class FirmPolicy
{
    public function view(User $user, Firm $firm): bool
    {
        return $user->can('firm.settings.manage') && (int) $user->firm_id === (int) $firm->id;
    }

    public function update(User $user, Firm $firm): bool
    {
        return $this->view($user, $firm);
    }
}

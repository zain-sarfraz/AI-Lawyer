<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaseLawRecord;
use App\Models\Lawyer;
use App\Models\User;

class LawyerPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'lawyers.manage';
    }

    protected function managePermission(): string
    {
        return 'lawyers.manage';
    }

    public function view(User $user, $model): bool
    {
        if (! $model instanceof Lawyer) {
            return false;
        }

        if (! $this->sameFirm($user, $model)) {
            return false;
        }

        if ($user->can('lawyers.manage')) {
            return $this->lawyerOwns($user, $model);
        }

        // Lawyers with cases.view may open their own profile / relevant cases.
        if ($user->can('cases.view') && $this->lawyerOwns($user, $model)) {
            return true;
        }

        return false;
    }

    public function viewRelevantCases(User $user, Lawyer $lawyer): bool
    {
        return $this->view($user, $lawyer);
    }

    public function viewCaseLaw(User $user, Lawyer $lawyer, CaseLawRecord $record): bool
    {
        if (! $this->view($user, $lawyer)) {
            return false;
        }

        if (! $record->isRealForUi()) {
            return false;
        }

        $ids = $lawyer->jurisdictions()->pluck('jurisdictions.id')->all();

        return in_array((int) $record->jurisdiction_id, array_map('intval', $ids), true);
    }
}

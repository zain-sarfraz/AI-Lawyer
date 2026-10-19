<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'appointments.view';
    }

    protected function managePermission(): string
    {
        return 'appointments.manage';
    }

    /**
     * Lawyers may update status on their own appointments without full booking rights.
     */
    public function changeStatus(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.view') || $user->can('appointments.manage'))
            && $this->sameFirm($user, $appointment)
            && $this->lawyerOwns($user, $appointment);
    }
}

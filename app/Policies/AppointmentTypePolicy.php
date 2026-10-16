<?php

declare(strict_types=1);

namespace App\Policies;

class AppointmentTypePolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'appointments.view';
    }

    protected function managePermission(): string
    {
        return 'appointments.manage';
    }
}

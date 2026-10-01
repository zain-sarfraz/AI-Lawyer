<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Note;

class NotePolicy extends TenantPolicy
{
    protected function viewPermission(): string
    {
        return 'clients.view';
    }

    protected function managePermission(): string
    {
        return 'clients.manage';
    }
}

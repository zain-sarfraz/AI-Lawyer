<?php

declare(strict_types=1);

namespace App\Domain\Firm;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionCatalog
{
    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            'firm.settings.manage' => 'Manage firm settings',
            'users.manage' => 'Manage users',
            'lawyers.manage' => 'Manage lawyers',
            'services.manage' => 'Manage services',
            'schedules.manage' => 'Manage schedules',
            'leads.view' => 'View leads',
            'leads.manage' => 'Manage leads',
            'clients.view' => 'View clients',
            'clients.manage' => 'Manage clients',
            'appointments.view' => 'View appointments',
            'appointments.manage' => 'Manage appointments',
            'calls.view' => 'View calls',
            'calls.manage' => 'Manage calls',
            'ai.settings.manage' => 'Manage AI settings',
            'integrations.manage' => 'Manage integrations',
            'audit.view' => 'View audit logs',
            'reports.view' => 'View reports',
            'knowledge.manage' => 'Manage knowledge base',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $all = array_keys(self::permissions());

        return [
            'Owner' => $all,
            'Admin' => $all,
            'Lawyer' => [
                'leads.view',
                'clients.view',
                'appointments.view',
                'appointments.manage',
                'calls.view',
                'schedules.manage',
                'reports.view',
            ],
            'Staff' => [
                'leads.view',
                'leads.manage',
                'clients.view',
                'clients.manage',
                'appointments.view',
                'appointments.manage',
                'calls.view',
                'calls.manage',
                'reports.view',
            ],
        ];
    }

    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissions() as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        foreach (self::matrix() as $roleName => $permissionNames) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                [],
            );
            $role->syncPermissions($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

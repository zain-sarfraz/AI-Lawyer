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
            // Platform (Superadmin only — never grants firm CRM access)
            'platform.dashboard.view' => 'View platform dashboard',
            'platform.firms.manage' => 'Manage firms',
            'platform.lawyers.manage' => 'Manage lawyers across firms',

            // Firm CRM
            'firm.settings.manage' => 'Manage firm settings',
            'users.manage' => 'Manage users',
            'lawyers.manage' => 'Manage lawyers',
            'invitations.manage' => 'Manage lawyer invitations',
            'services.manage' => 'Manage services',
            'schedules.manage' => 'Manage schedules',
            'leads.view' => 'View leads',
            'leads.manage' => 'Manage leads',
            'clients.view' => 'View clients',
            'clients.manage' => 'Manage clients',
            'cases.view' => 'View cases',
            'cases.manage' => 'Manage cases',
            'documents.view' => 'View CRM documents',
            'documents.manage' => 'Manage CRM documents',
            'appointments.view' => 'View appointments',
            'appointments.manage' => 'Manage appointments',
            'calls.view' => 'View calls',
            'calls.manage' => 'Manage calls',
            'ai.settings.manage' => 'Manage AI settings',
            'integrations.manage' => 'Manage integrations',
            'audit.view' => 'View audit logs',
            'reports.view' => 'View reports',
            'knowledge.manage' => 'Manage knowledge base',
            'billing.view' => 'View subscription & billing',
        ];
    }

    /**
     * Roles: Superadmin (platform), FirmAdmin (firm owner), Lawyer (individual / firm member).
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $allFirmPermissions = array_values(array_filter(
            array_keys(self::permissions()),
            fn (string $name) => ! str_starts_with($name, 'platform.')
        ));

        // Individual lawyers keep full desk access; firm-wide seat/invite control stays with FirmAdmin.
        $lawyerPermissions = array_values(array_filter(
            $allFirmPermissions,
            fn (string $name) => ! in_array($name, ['lawyers.manage', 'invitations.manage', 'users.manage', 'billing.view'], true)
        ));

        return [
            'Superadmin' => [
                'platform.dashboard.view',
                'platform.firms.manage',
                'platform.lawyers.manage',
            ],
            'FirmAdmin' => $allFirmPermissions,
            'Lawyer' => $lawyerPermissions,
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

        $keptRoles = [];
        foreach (self::matrix() as $roleName => $permissionNames) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                [],
            );
            $role->syncPermissions($permissionNames);
            $keptRoles[] = $roleName;
        }

        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $keptRoles)
            ->each(function (Role $role): void {
                $role->permissions()->detach();
                $role->users()->detach();
                $role->delete();
            });

        // Drop retired platform permission if it still exists.
        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'platform.admins.manage')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

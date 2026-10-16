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
            'platform.admins.manage' => 'Create and manage firm admins',

            // Firm CRM
            'firm.settings.manage' => 'Manage firm settings',
            'users.manage' => 'Manage users',
            'lawyers.manage' => 'Manage lawyers',
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
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $firmPermissions = array_values(array_filter(
            array_keys(self::permissions()),
            fn (string $name) => ! str_starts_with($name, 'platform.')
        ));

        return [
            'Superadmin' => [
                'platform.dashboard.view',
                'platform.firms.manage',
                'platform.admins.manage',
            ],
            'Owner' => $firmPermissions,
            'Admin' => $firmPermissions,
            'Lawyer' => [
                // No leads/clients menu — clients appear via their appointments & cases.
                // Lawyers can view appointments but cannot book (Admin / AI agent only).
                'cases.view',
                'cases.manage',
                'documents.view',
                'documents.manage',
                'appointments.view',
                'calls.view',
                'schedules.manage',
                'reports.view',
            ],
            'Staff' => [
                'leads.view',
                'leads.manage',
                'clients.view',
                'clients.manage',
                'cases.view',
                'cases.manage',
                'documents.view',
                'documents.manage',
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

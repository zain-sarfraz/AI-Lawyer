<?php

use App\Domain\Firm\PermissionCatalog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure target roles exist before remapping users.
        $lawyerRole = Role::query()->firstOrCreate(
            ['name' => 'Lawyer', 'guard_name' => 'web'],
        );
        Role::query()->firstOrCreate(
            ['name' => 'Superadmin', 'guard_name' => 'web'],
        );

        $legacyRoleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Owner', 'Staff', 'Admin'])
            ->pluck('id');

        if ($legacyRoleIds->isNotEmpty()) {
            $legacyUserIds = DB::table('model_has_roles')
                ->whereIn('role_id', $legacyRoleIds)
                ->where('model_type', User::class)
                ->pluck('model_id')
                ->unique();

            foreach ($legacyUserIds as $userId) {
                $alreadyLawyer = DB::table('model_has_roles')
                    ->where('role_id', $lawyerRole->id)
                    ->where('model_type', User::class)
                    ->where('model_id', $userId)
                    ->exists();

                if (! $alreadyLawyer) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $lawyerRole->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ]);
                }
            }

            DB::table('model_has_roles')
                ->whereIn('role_id', $legacyRoleIds)
                ->where('model_type', User::class)
                ->delete();
        }

        // Firm users with no role (detached when Owner/Staff were deleted) become Lawyers.
        $firmUsersWithoutRole = User::query()
            ->withoutGlobalScopes()
            ->whereNotNull('firm_id')
            ->whereDoesntHave('roles')
            ->pluck('id');

        foreach ($firmUsersWithoutRole as $userId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $lawyerRole->id,
                'model_type' => User::class,
                'model_id' => $userId,
            ]);
        }

        PermissionCatalog::sync();
    }

    public function down(): void
    {
        PermissionCatalog::sync();
    }
};

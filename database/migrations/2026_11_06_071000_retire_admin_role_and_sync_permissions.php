<?php

use App\Domain\Firm\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync();

        $adminRole = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        $ownerRole = Role::query()->where('name', 'Owner')->where('guard_name', 'web')->first();

        if ($adminRole && $ownerRole) {
            $adminUserIds = DB::table('model_has_roles')
                ->where('role_id', $adminRole->id)
                ->where('model_type', \App\Models\User::class)
                ->pluck('model_id');

            foreach ($adminUserIds as $userId) {
                $alreadyOwner = DB::table('model_has_roles')
                    ->where('role_id', $ownerRole->id)
                    ->where('model_type', \App\Models\User::class)
                    ->where('model_id', $userId)
                    ->exists();

                if (! $alreadyOwner) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $ownerRole->id,
                        'model_type' => \App\Models\User::class,
                        'model_id' => $userId,
                    ]);
                }

                DB::table('model_has_roles')
                    ->where('role_id', $adminRole->id)
                    ->where('model_type', \App\Models\User::class)
                    ->where('model_id', $userId)
                    ->delete();
            }

            DB::table('role_has_permissions')->where('role_id', $adminRole->id)->delete();
            $adminRole->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        PermissionCatalog::sync();
    }

    public function down(): void
    {
        // Admin role is intentionally not restored.
        PermissionCatalog::sync();
    }
};

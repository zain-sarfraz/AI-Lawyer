<?php

use App\Domain\Firm\PermissionCatalog;
use App\Models\Scopes\FirmScope;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync();

        if (Schema::hasColumn('users', 'legacy_role')) {
            DB::table('users')->whereNotNull('legacy_role')->orderBy('id')->each(function (object $row): void {
                $role = match ($row->legacy_role) {
                    'admin' => $row->email === 'admin@lawfirm.test' ? 'Owner' : 'Admin',
                    'lawyer' => 'Lawyer',
                    'staff' => 'Staff',
                    default => 'Staff',
                };

                $user = User::query()->withoutGlobalScope(FirmScope::class)->find($row->id);
                $user?->assignRole($role);
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('legacy_role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('legacy_role')->nullable();
        });
    }
};

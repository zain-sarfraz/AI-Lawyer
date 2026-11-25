<?php

declare(strict_types=1);

use App\Domain\Firm\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('firm_invitations')) {
            Schema::create('firm_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 50)->nullable();
                $table->string('role', 32)->default('Lawyer');
                $table->string('token', 64)->unique();
                $table->string('status', 32)->default('pending');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('email_dispatched_at')->nullable();
                $table->text('email_error')->nullable();
                $table->timestamps();

                $table->unique(['firm_id', 'email']);
                $table->index(['firm_id', 'status']);
            });
        }

        PermissionCatalog::sync();

        // Promote Growth/firm workspace owners (first active lawyer user) to FirmAdmin.
        $firmIds = DB::table('firms')
            ->where(function ($q) {
                $q->where('account_type', 'firm')
                    ->orWhere('plan', 'growth');
            })
            ->pluck('id');

        foreach ($firmIds as $firmId) {
            $ownerUserId = DB::table('lawyers')
                ->where('firm_id', $firmId)
                ->where('status', 'active')
                ->orderBy('id')
                ->value('user_id');

            if (! $ownerUserId) {
                $ownerUserId = DB::table('users')
                    ->where('firm_id', $firmId)
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $ownerUserId) {
                continue;
            }

            $firmAdminRoleId = DB::table('roles')
                ->where('name', 'FirmAdmin')
                ->where('guard_name', 'web')
                ->value('id');

            if (! $firmAdminRoleId) {
                continue;
            }

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $ownerUserId)
                ->delete();

            DB::table('model_has_roles')->insert([
                'role_id' => $firmAdminRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $ownerUserId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_invitations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform Superadmins have no firm — allow NULL firm_id.
        DB::statement('ALTER TABLE `users` MODIFY `firm_id` BIGINT UNSIGNED NULL');

        Schema::table('firms', function (Blueprint $table) {
            if (! Schema::hasColumn('firms', 'status')) {
                $table->string('status', 32)->default('active')->after('slug');
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            if (Schema::hasColumn('firms', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};

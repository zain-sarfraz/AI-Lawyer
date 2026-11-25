<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM users'))->pluck('Key_name');

        Schema::table('users', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('users_email_unique')) {
                $table->dropUnique(['email']);
            }
            if (! $indexes->contains('users_firm_id_email_unique')) {
                // Same email may exist in different firms; not twice inside one firm.
                $table->unique(['firm_id', 'email']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['firm_id', 'email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};

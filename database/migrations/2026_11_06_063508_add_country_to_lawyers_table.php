<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lawyers', function (Blueprint $table) {
            if (! Schema::hasColumn('lawyers', 'country')) {
                $table->string('country', 128)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lawyers', function (Blueprint $table) {
            if (Schema::hasColumn('lawyers', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};

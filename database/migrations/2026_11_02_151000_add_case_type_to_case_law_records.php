<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_law_records', function (Blueprint $table) {
            $table->string('case_type', 64)->nullable()->after('citation')->index();
        });
    }

    public function down(): void
    {
        Schema::table('case_law_records', function (Blueprint $table) {
            $table->dropColumn('case_type');
        });
    }
};

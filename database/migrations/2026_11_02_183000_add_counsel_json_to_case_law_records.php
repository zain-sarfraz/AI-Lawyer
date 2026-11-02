<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('case_law_records', 'counsel_json')) {
            Schema::table('case_law_records', function (Blueprint $table) {
                $table->json('counsel_json')->nullable()->after('author_judge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('case_law_records', 'counsel_json')) {
            Schema::table('case_law_records', function (Blueprint $table) {
                $table->dropColumn('counsel_json');
            });
        }
    }
};

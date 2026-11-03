<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courts', 'external_court_id')) {
            Schema::table('courts', function (Blueprint $table) {
                $table->string('external_court_id', 64)->nullable()->after('level')->index();
            });
        }

        Schema::table('jurisdictions', function (Blueprint $table) {
            if (! Schema::hasColumn('jurisdictions', 'last_case_fetch_at')) {
                $table->timestamp('last_case_fetch_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('jurisdictions', 'last_case_fetch_status')) {
                $table->string('last_case_fetch_status', 32)->nullable()->after('last_case_fetch_at');
            }
            if (! Schema::hasColumn('jurisdictions', 'last_case_fetch_message')) {
                $table->string('last_case_fetch_message', 500)->nullable()->after('last_case_fetch_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            if (Schema::hasColumn('courts', 'external_court_id')) {
                $table->dropColumn('external_court_id');
            }
        });

        Schema::table('jurisdictions', function (Blueprint $table) {
            foreach (['last_case_fetch_at', 'last_case_fetch_status', 'last_case_fetch_message'] as $col) {
                if (Schema::hasColumn('jurisdictions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

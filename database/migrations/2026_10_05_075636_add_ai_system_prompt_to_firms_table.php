<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            if (! Schema::hasColumn('firms', 'ai_system_prompt')) {
                $table->longText('ai_system_prompt')->nullable()->after('ai_escalation_rules');
            }
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            if (Schema::hasColumn('firms', 'ai_system_prompt')) {
                $table->dropColumn('ai_system_prompt');
            }
        });
    }
};

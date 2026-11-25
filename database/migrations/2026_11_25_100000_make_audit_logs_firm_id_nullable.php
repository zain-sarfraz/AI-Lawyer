<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'firm_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('firm_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Keep nullable to avoid breaking existing platform/solo audit log rows
    }
};

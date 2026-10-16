<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_documents', function (Blueprint $table) {
            $table->string('status', 32)->default('open')->after('notes');
            $table->index(['firm_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_documents', function (Blueprint $table) {
            $table->dropIndex(['firm_id', 'status']);
            $table->dropColumn('status');
        });
    }
};

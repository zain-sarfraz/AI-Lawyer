<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lawyer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('case_number', 64)->nullable();
            $table->string('title');
            $table->string('status', 32)->default('open')->index();
            $table->text('description')->nullable();
            $table->date('opened_on')->nullable();
            $table->timestamps();
            $table->index(['firm_id', 'status']);
        });

        Schema::create('crm_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('original_name');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['firm_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_documents');
        Schema::dropIfExists('legal_cases');
    }
};

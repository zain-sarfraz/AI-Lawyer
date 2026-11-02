<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('country_code', 2)->index();
            $table->string('name');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurisdiction_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('level', 32); // supreme|high|appeal|tribunal
            $table->timestamps();
            $table->index(['jurisdiction_id', 'level']);
        });

        // Public case-law corpus (NOT firm CRM legal_cases).
        Schema::create('case_law_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurisdiction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('source');
            $table->string('title');
            $table->string('citation')->nullable();
            $table->date('decision_date')->nullable();
            $table->string('source_url')->nullable();
            $table->longText('full_text')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('winner')->nullable();
            $table->json('evidence_json')->nullable();
            $table->string('ai_status', 32)->default('pending')->index(); // pending|processing|done|failed
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('jurisdiction_id');
            $table->index('decision_date');
        });

        Schema::create('lawyer_jurisdiction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['lawyer_id', 'jurisdiction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_jurisdiction');
        Schema::dropIfExists('case_law_records');
        Schema::dropIfExists('courts');
        Schema::dropIfExists('jurisdictions');
    }
};

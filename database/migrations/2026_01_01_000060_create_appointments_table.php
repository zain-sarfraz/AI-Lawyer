<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 32)->default('scheduled')->index();
            $table->string('source', 64)->nullable();
            $table->string('external_event_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['lawyer_id', 'start_at', 'end_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

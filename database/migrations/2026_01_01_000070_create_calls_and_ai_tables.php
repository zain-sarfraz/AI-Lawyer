<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('provider_call_id')->nullable()->unique();
            $table->string('direction', 32)->default('inbound');
            $table->string('caller_phone')->nullable()->index();
            $table->foreignId('lawyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 32)->default('initiated')->index();
            $table->string('recording_url')->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->string('intent')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->cascadeOnDelete();
            $table->string('provider_session_id')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('audio_seconds')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_session_id')->constrained()->cascadeOnDelete();
            $table->string('tool_name');
            $table->json('arguments_json')->nullable();
            $table->json('result_json')->nullable();
            $table->string('status', 32)->default('success');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
            $table->index(['ai_session_id', 'tool_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_calls');
        Schema::dropIfExists('ai_sessions');
        Schema::dropIfExists('calls');
    }
};

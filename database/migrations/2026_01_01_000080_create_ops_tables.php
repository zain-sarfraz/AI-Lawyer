<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->default('faq');
            $table->string('title');
            $table->longText('content');
            $table->string('status', 32)->default('active');
            $table->boolean('ai_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('channel', 32);
            $table->string('recipient');
            $table->nullableMorphs('related');
            $table->string('status', 32)->default('pending')->index();
            $table->string('provider_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->string('status', 32)->default('inactive');
            $table->text('encrypted_credentials')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values_json')->nullable();
            $table->json('new_values_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('callback_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('call_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('knowledge_base_items');
    }
};

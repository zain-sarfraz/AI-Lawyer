<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lawyer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 64)->default('new')->index();
            $table->string('source', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_lead_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unique(['client_id', 'lead_id']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('notable');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('client_lead_links');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('leads');
    }
};

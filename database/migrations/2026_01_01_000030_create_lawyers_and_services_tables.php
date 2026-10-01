<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->json('specialties')->nullable();
            $table->string('timezone')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('buffer_minutes')->nullable();
            $table->boolean('booking_enabled')->default(true);
            $table->boolean('ai_enabled')->default(true);
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('lawyer_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['lawyer_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('lawyers');
    }
};

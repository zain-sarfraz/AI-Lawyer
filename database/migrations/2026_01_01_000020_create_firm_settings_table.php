<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('firm_name');
            $table->string('legal_name')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('currency', 8)->default('USD');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 32)->nullable();
            $table->string('receptionist_name')->nullable();
            $table->text('email_footer')->nullable();
            $table->unsignedInteger('slot_interval_minutes')->default(15);
            $table->unsignedInteger('default_duration_minutes')->default(30);
            $table->unsignedInteger('buffer_minutes')->default(0);
            $table->unsignedInteger('booking_horizon_days')->default(60);
            $table->unsignedInteger('cancellation_window_hours')->default(24);
            $table->string('ai_greeting')->nullable();
            $table->string('ai_tone')->nullable();
            $table->string('ai_language')->default('en');
            $table->string('ai_voice')->nullable();
            $table->text('ai_allowed_topics')->nullable();
            $table->text('ai_escalation_rules')->nullable();
            $table->string('twilio_phone_number')->nullable();
            $table->string('transfer_number')->nullable();
            $table->string('after_hours_behavior')->default('callback');
            $table->boolean('recording_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_settings');
    }
};

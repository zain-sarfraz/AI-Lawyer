<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday .. 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->index(['lawyer_id', 'day_of_week']);
        });

        Schema::create('schedule_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->unique('date');
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('type', 32); // available | unavailable
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['lawyer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_exceptions');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('schedule_breaks');
        Schema::dropIfExists('working_hours');
    }
};

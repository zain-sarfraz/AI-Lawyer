<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'client_number')) {
                $table->string('client_number', 64)->nullable()->after('name')->index();
            }
        });

        if (! Schema::hasTable('client_service')) {
            Schema::create('client_service', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->unique(['client_id', 'service_id']);
            });
        }

        Schema::table('lawyers', function (Blueprint $table) {
            if (! Schema::hasColumn('lawyers', 'phone')) {
                $table->string('phone', 50)->nullable()->after('title');
            }
            if (! Schema::hasColumn('lawyers', 'experience')) {
                $table->string('experience', 255)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service');

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'client_number')) {
                $table->dropColumn('client_number');
            }
        });

        Schema::table('lawyers', function (Blueprint $table) {
            $columns = collect(['phone', 'experience'])
                ->filter(fn (string $column) => Schema::hasColumn('lawyers', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

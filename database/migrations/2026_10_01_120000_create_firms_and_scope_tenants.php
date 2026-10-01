<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tenantTables = [
        'users',
        'lawyers',
        'service_categories',
        'services',
        'leads',
        'clients',
        'notes',
        'working_hours',
        'schedule_breaks',
        'holidays',
        'availability_exceptions',
        'appointments',
        'calls',
        'ai_sessions',
        'ai_tool_calls',
        'knowledge_base_items',
        'notifications',
        'integrations',
        'audit_logs',
        'callback_requests',
    ];

    public function up(): void
    {
        $this->repairLegacyAppointmentDatetimes();

        if (! Schema::hasTable('firms')) {
            $this->createFirmsTable();
        }

        if (Schema::hasTable('firm_settings')) {
            foreach (DB::table('firm_settings')->orderBy('id')->get() as $row) {
                DB::table('firms')->insert((array) $row);
            }
            $max = (int) DB::table('firms')->max('id');
            if ($max > 0) {
                DB::statement('ALTER TABLE firms AUTO_INCREMENT = '.($max + 1));
            }
            Schema::drop('firm_settings');
        }

        foreach ($this->tenantTables as $table) {
            if (Schema::hasColumn($table, 'firm_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('firm_id')->nullable()->after('id')->constrained('firms')->cascadeOnDelete();
            });
        }

        $firmId = DB::table('firms')->orderBy('id')->value('id');
        if ($firmId === null) {
            $hasRows = false;
            foreach ($this->tenantTables as $table) {
                if (DB::table($table)->exists()) {
                    $hasRows = true;
                    break;
                }
            }
            if ($hasRows) {
                $firmId = DB::table('firms')->insertGetId([
                    'firm_name' => 'Law Firm',
                    'timezone' => 'Asia/Karachi',
                    'currency' => 'USD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($firmId !== null) {
            foreach ($this->tenantTables as $table) {
                DB::table($table)->whereNull('firm_id')->update(['firm_id' => $firmId]);
            }
        }

        foreach ($this->tenantTables as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `firm_id` BIGINT UNSIGNED NOT NULL");
        }

        $holidayIndexes = collect(DB::select('SHOW INDEX FROM holidays'))->pluck('Key_name');
        Schema::table('holidays', function (Blueprint $table) use ($holidayIndexes) {
            if ($holidayIndexes->contains('holidays_date_unique')) {
                $table->dropUnique(['date']);
            }
            if (! $holidayIndexes->contains('holidays_firm_id_date_unique')) {
                $table->unique(['firm_id', 'date']);
            }
        });

        $integrationIndexes = collect(DB::select('SHOW INDEX FROM integrations'))->pluck('Key_name');
        Schema::table('integrations', function (Blueprint $table) use ($integrationIndexes) {
            if ($integrationIndexes->contains('integrations_provider_unique')) {
                $table->dropUnique(['provider']);
            }
            if (! $integrationIndexes->contains('integrations_firm_id_provider_unique')) {
                $table->unique(['firm_id', 'provider']);
            }
        });
    }

    private function repairLegacyAppointmentDatetimes(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        $end = DB::selectOne("SHOW COLUMNS FROM appointments WHERE Field = 'end_at'");
        if ($end === null || ! str_contains(strtolower((string) $end->Type), 'timestamp')) {
            return;
        }

        $originalMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("UPDATE appointments SET end_at = start_at WHERE end_at = '0000-00-00 00:00:00' OR end_at IS NULL");
        DB::statement('ALTER TABLE appointments MODIFY start_at DATETIME NULL, MODIFY end_at DATETIME NULL');
        DB::statement('SET SESSION sql_mode = ?', [$originalMode]);
    }

    private function createFirmsTable(): void
    {
        Schema::create('firms', function (Blueprint $table) {
            $table->id();
            $table->string('firm_name');
            $table->string('legal_name')->nullable();
            $table->string('timezone')->default('Asia/Karachi');
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
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropUnique(['firm_id', 'provider']);
            $table->unique('provider');
        });
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique(['firm_id', 'date']);
            $table->unique('date');
        });

        foreach (array_reverse($this->tenantTables) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('firm_id');
            });
        }

        Schema::dropIfExists('firms');
    }
};

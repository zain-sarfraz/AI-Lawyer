<?php

use App\Domain\Scheduling\DefaultAppointmentTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('firm_name');
            $table->unsignedSmallInteger('min_notice_minutes')->default(60)->after('booking_horizon_days');
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0)->after('buffer_minutes');
            $table->string('public_booking_status', 32)->default('pending')->after('cancellation_window_hours');
        });

        Schema::table('lawyers', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('user_id');
            $table->unique(['firm_id', 'slug']);
        });

        foreach (DB::table('firms')->orderBy('id')->get() as $firm) {
            $base = Str::slug((string) $firm->firm_name) ?: 'firm';
            $slug = $base;
            $suffix = 2;
            while (DB::table('firms')->where('slug', $slug)->where('id', '!=', $firm->id)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }
            DB::table('firms')->where('id', $firm->id)->update(['slug' => $slug]);
        }

        foreach (DB::table('lawyers')->orderBy('id')->get() as $lawyer) {
            $name = DB::table('users')->where('id', $lawyer->user_id)->value('name');
            $base = Str::slug((string) $name) ?: 'lawyer';
            DB::table('lawyers')->where('id', $lawyer->id)->update([
                'slug' => $base.'-'.$lawyer->user_id,
            ]);
        }

        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->unique(['firm_id', 'slug']);
        });

        Schema::create('lawyer_appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['lawyer_id', 'appointment_type_id']);
        });

        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->string('recurrence', 16);
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lawyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->date('range_start');
            $table->date('range_end');
            $table->string('status', 32)->default('waiting');
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE appointments MODIFY service_id BIGINT UNSIGNED NULL');

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('appointment_type_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->foreignId('rescheduled_from_id')->nullable()->after('appointment_type_id')->constrained('appointments')->nullOnDelete();
            $table->text('case_summary')->nullable()->after('notes');
            $table->decimal('fee_amount', 10, 2)->nullable()->after('case_summary');
            $table->string('active_slot_key')->nullable()->after('status');
            $table->dateTime('reminder_24h_sent_at')->nullable()->after('active_slot_key');
            $table->dateTime('reminder_1h_sent_at')->nullable()->after('reminder_24h_sent_at');
        });

        DB::table('appointments')->where('status', 'scheduled')->update(['status' => 'pending']);
        DB::table('appointments')->where('status', 'in_progress')->update(['status' => 'confirmed']);
        DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending'");

        $seen = [];
        $rows = DB::table('appointments')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('id')
            ->get(['id', 'lawyer_id', 'start_at']);

        foreach ($rows as $row) {
            if ($row->start_at === null || $row->lawyer_id === null) {
                continue;
            }

            $key = $row->lawyer_id.':'.Carbon::parse($row->start_at)->utc()->format('Y-m-d H:i:s');
            if (isset($seen[$key])) {
                DB::table('appointments')->where('id', $row->id)->update([
                    'status' => 'cancelled',
                    'active_slot_key' => null,
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\nCancelled during slot-key migration because the start time was already booked.')"),
                ]);

                continue;
            }

            $seen[$key] = true;
            DB::table('appointments')->where('id', $row->id)->update(['active_slot_key' => $key]);
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('active_slot_key');
        });

        foreach (DB::table('firms')->get(['id', 'currency']) as $firm) {
            DefaultAppointmentTypes::seedFirm((int) $firm->id, (string) ($firm->currency ?: 'USD'));
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['active_slot_key']);
            $table->dropConstrainedForeignId('appointment_type_id');
            $table->dropConstrainedForeignId('rescheduled_from_id');
            $table->dropColumn([
                'case_summary', 'fee_amount', 'active_slot_key',
                'reminder_24h_sent_at', 'reminder_1h_sent_at',
            ]);
        });

        DB::table('appointments')->where('status', 'pending')->update(['status' => 'scheduled']);
        DB::table('appointments')->where('status', 'rescheduled')->update(['status' => 'cancelled']);
        DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(32) NOT NULL DEFAULT 'scheduled'");
        DB::statement('ALTER TABLE appointments MODIFY service_id BIGINT UNSIGNED NOT NULL');

        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('lawyer_appointment_types');
        Schema::dropIfExists('appointment_types');

        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropUnique(['firm_id', 'slug']);
            $table->dropColumn('slug');
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn(['slug', 'min_notice_minutes', 'buffer_before_minutes', 'public_booking_status']);
        });
    }
};

<?php

namespace Tests\Unit;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\Firm\FirmContext;
use App\Domain\Scheduling\AvailabilityService;
use App\Models\Appointment;
use App\Models\FirmSetting;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_booking_of_same_slot_conflicts(): void
    {
        $this->seedMinimalSchedule();

        $service = Service::query()->first();
        $lawyer = Lawyer::query()->first();
        $firm = FirmSetting::query()->first();
        $timezone = $lawyer->timezone ?: $firm->timezone;

        // Next weekday at 10:00 local
        $date = Carbon::now($timezone)->next(Carbon::TUESDAY)->setTime(10, 0);

        /** @var AppointmentBookingService $booking */
        $booking = $this->app->make(AppointmentBookingService::class);

        $first = $booking->book([
            'service_id' => $service->id,
            'lawyer_id' => $lawyer->id,
            'start_at' => $date->toIso8601String(),
            'name' => 'Caller A',
            'phone' => '+15550101',
            'source' => 'test',
            'idempotency_key' => 'a-1',
        ]);

        $second = $booking->book([
            'service_id' => $service->id,
            'lawyer_id' => $lawyer->id,
            'start_at' => $date->toIso8601String(),
            'name' => 'Caller B',
            'phone' => '+15550102',
            'source' => 'test',
            'idempotency_key' => 'b-1',
        ]);

        $this->assertTrue($first->success);
        $this->assertFalse($second->success);
        $this->assertSame('BOOKING_CONFLICT', $second->code);
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_availability_excludes_booked_slot(): void
    {
        $this->seedMinimalSchedule();
        $service = Service::query()->first();
        $lawyer = Lawyer::query()->first();
        $firm = FirmSetting::query()->first();
        $timezone = $lawyer->timezone ?: $firm->timezone;
        $date = Carbon::now($timezone)->next(Carbon::WEDNESDAY)->setTime(10, 0);

        $booking = $this->app->make(AppointmentBookingService::class);
        $availability = $this->app->make(AvailabilityService::class);

        $booking->book([
            'service_id' => $service->id,
            'lawyer_id' => $lawyer->id,
            'start_at' => $date->toIso8601String(),
            'name' => 'Caller A',
            'phone' => '+15550103',
            'source' => 'test',
        ]);

        $slots = $availability->findSlots($service, $date, $lawyer);
        $starts = collect($slots)->pluck('start_at')->all();
        $this->assertNotContains($date->copy()->utc()->toIso8601String(), $starts);
    }

    private function seedMinimalSchedule(): void
    {
        $firm = FirmSetting::query()->create([
            'firm_name' => 'Test Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'slot_interval_minutes' => 30,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 60,
            'cancellation_window_hours' => 24,
        ]);
        app(FirmContext::class)->set($firm->id);

        $user = User::query()->create([
            'name' => 'Test Lawyer',
            'email' => 'lawyer-test@example.com',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $lawyer = Lawyer::query()->create([
            'user_id' => $user->id,
            'title' => 'Counsel',
            'status' => 'active',
            'timezone' => 'UTC',
        ]);

        $service = Service::query()->create([
            'name' => 'Consult',
            'price' => 100,
            'currency' => 'USD',
            'duration_minutes' => 30,
            'booking_enabled' => true,
            'ai_enabled' => true,
            'status' => 'active',
        ]);
        $lawyer->services()->attach($service->id);

        foreach ([1, 2, 3, 4, 5] as $day) {
            WorkingHour::query()->create([
                'lawyer_id' => $lawyer->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'status' => 'active',
            ]);
        }
    }
}

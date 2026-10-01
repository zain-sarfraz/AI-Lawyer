<?php

namespace Database\Factories;

use App\Models\Firm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Firm>
 */
class FirmFactory extends Factory
{
    protected $model = Firm::class;

    public function definition(): array
    {
        return [
            'firm_name' => fake()->company(),
            'timezone' => Firm::DEFAULT_TIMEZONE,
            'currency' => 'USD',
            'receptionist_name' => 'Alex',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 60,
            'cancellation_window_hours' => 24,
        ];
    }
}

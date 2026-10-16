<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use Illuminate\Support\Facades\DB;

class DefaultAppointmentTypes
{
    /**
     * @var list<array{slug: string, name: string, duration_minutes: int, fee: float}>
     */
    public const TYPES = [
        ['slug' => 'initial-consultation', 'name' => 'Initial consultation', 'duration_minutes' => 60, 'fee' => 250],
        ['slug' => 'follow-up', 'name' => 'Follow-up', 'duration_minutes' => 30, 'fee' => 150],
        ['slug' => 'court-prep', 'name' => 'Court prep', 'duration_minutes' => 45, 'fee' => 200],
    ];

    public static function seedFirm(int $firmId, string $currency = 'USD'): void
    {
        foreach (self::TYPES as $type) {
            $exists = DB::table('appointment_types')
                ->where('firm_id', $firmId)
                ->where('slug', $type['slug'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('appointment_types')->insert([
                'firm_id' => $firmId,
                'name' => $type['name'],
                'slug' => $type['slug'],
                'duration_minutes' => $type['duration_minutes'],
                'fee' => $type['fee'],
                'currency' => $currency,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

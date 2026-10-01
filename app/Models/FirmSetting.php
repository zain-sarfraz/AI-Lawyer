<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmSetting extends Model
{
    protected $fillable = [
        'firm_name', 'legal_name', 'timezone', 'currency', 'address', 'phone', 'email',
        'website', 'logo_path', 'favicon_path', 'primary_color', 'receptionist_name',
        'email_footer', 'slot_interval_minutes', 'default_duration_minutes', 'buffer_minutes',
        'booking_horizon_days', 'cancellation_window_hours', 'ai_greeting', 'ai_tone',
        'ai_language', 'ai_voice', 'ai_allowed_topics', 'ai_escalation_rules',
        'twilio_phone_number', 'transfer_number', 'after_hours_behavior', 'recording_enabled',
    ];

    protected function casts(): array
    {
        return [
            'recording_enabled' => 'boolean',
            'slot_interval_minutes' => 'integer',
            'default_duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'booking_horizon_days' => 'integer',
            'cancellation_window_hours' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}

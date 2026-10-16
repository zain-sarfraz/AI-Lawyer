<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Firm\FirmContext;
use Database\Factories\FirmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Firm extends Model
{
    /** @use HasFactory<FirmFactory> */
    use HasFactory;

    public const DEFAULT_TIMEZONE = 'Asia/Karachi';

    protected $table = 'firms';

    protected $fillable = [
        'firm_name', 'legal_name', 'timezone', 'currency', 'address', 'phone', 'email',
        'website', 'logo_path', 'favicon_path', 'primary_color', 'receptionist_name',
        'email_footer', 'slot_interval_minutes', 'default_duration_minutes', 'buffer_minutes',
        'booking_horizon_days', 'cancellation_window_hours', 'min_notice_minutes',
        'buffer_before_minutes', 'public_booking_status', 'slug', 'ai_greeting', 'ai_tone',
        'ai_language', 'ai_voice', 'ai_allowed_topics', 'ai_escalation_rules', 'ai_system_prompt',
        'twilio_phone_number', 'transfer_number', 'after_hours_behavior', 'recording_enabled',
        'status',
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
            'min_notice_minutes' => 'integer',
            'buffer_before_minutes' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    protected static function booted(): void
    {
        static::creating(function (Firm $firm): void {
            if (! empty($firm->slug)) {
                return;
            }

            $base = Str::slug((string) $firm->firm_name) ?: 'firm';
            $slug = $base;
            $suffix = 2;
            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }
            $firm->slug = $slug;
        });
    }

    public function resolvedTimezone(): string
    {
        $timezone = $this->timezone;

        return is_string($timezone) && $timezone !== '' ? $timezone : self::DEFAULT_TIMEZONE;
    }

    public static function current(): self
    {
        $firmId = app(FirmContext::class)->id();
        if ($firmId === null) {
            throw new \RuntimeException('No firm context is set.');
        }

        return static::query()->findOrFail($firmId);
    }

    public function users(): HasMany
    {
        // Relation must ignore FirmScope so platform portal can count/list firm members.
        return $this->hasMany(User::class)->withoutGlobalScopes();
    }
}

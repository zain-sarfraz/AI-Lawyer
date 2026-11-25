<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Firm\FirmContext;
use Database\Factories\FirmFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'name', 'owner_id', 'firm_name', 'legal_name', 'timezone', 'currency', 'address', 'phone', 'email',
        'website', 'logo_path', 'favicon_path', 'primary_color', 'receptionist_name',
        'email_footer', 'slot_interval_minutes', 'default_duration_minutes', 'buffer_minutes',
        'booking_horizon_days', 'cancellation_window_hours', 'min_notice_minutes',
        'buffer_before_minutes', 'public_booking_status', 'slug', 'ai_greeting', 'ai_tone',
        'ai_language', 'ai_voice', 'ai_allowed_topics', 'ai_escalation_rules', 'ai_system_prompt',
        'twilio_phone_number', 'transfer_number', 'after_hours_behavior', 'recording_enabled',
        'status', 'account_type', 'plan', 'billing_interval', 'subscription_status', 'max_lawyers',
        'stripe_customer_id', 'stripe_subscription_id', 'stripe_payment_intent_id', 'subscribed_at',
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
            'max_lawyers' => 'integer',
            'subscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function isIndividualAccount(): bool
    {
        return ($this->account_type ?? 'firm') === 'individual' || ($this->plan ?? null) === 'starter';
    }

    public function isFirmAccount(): bool
    {
        return ! $this->isIndividualAccount();
    }

    public function allowsMultipleLawyers(): bool
    {
        $max = $this->resolvedMaxLawyers();

        return $max === null;
    }

    public function resolvedMaxLawyers(): ?int
    {
        if ($this->max_lawyers !== null) {
            return (int) $this->max_lawyers;
        }

        if (($this->plan ?? null) === 'starter' || ($this->account_type ?? null) === 'individual') {
            return 1;
        }

        return null;
    }

    public function lawyerCount(): int
    {
        return Lawyer::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $this->id)
            ->where('status', 'active')
            ->count();
    }

    public function canAddLawyer(): bool
    {
        $max = $this->resolvedMaxLawyers();
        if ($max === null) {
            return true;
        }

        return $this->lawyerCount() < $max;
    }

    public function isIndividualWorkspace(): bool
    {
        if ($this->isIndividualAccount()) {
            return true;
        }

        if ($this->relationLoaded('owner') && ($this->owner?->isIndividual() ?? false)) {
            return true;
        }

        if ($this->relationLoaded('users') && $this->users->contains(fn (User $u) => $u->isIndividual())) {
            return true;
        }

        return false;
    }

    public function workspaceDisplayName(): string
    {
        if (! $this->isIndividualWorkspace()) {
            return (string) $this->firm_name;
        }

        if ($this->relationLoaded('owner') && ! empty($this->owner?->name)) {
            return (string) $this->owner->name;
        }

        if ($this->relationLoaded('users')) {
            $member = $this->users->first();
            if ($member && ! empty($member->name)) {
                return (string) $member->name;
            }
        }

        return (string) $this->firm_name;
    }

    public function scopeLawFirms(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereNull('account_type')->orWhere('account_type', '!=', 'individual');
            })
            ->where(function (Builder $q): void {
                $q->whereNull('plan')->orWhere('plan', '!=', 'starter');
            });
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withoutGlobalScopes();
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Firm $firm): void {
            if (! empty($firm->name) && empty($firm->firm_name)) {
                $firm->firm_name = $firm->name;
            } elseif (! empty($firm->firm_name) && empty($firm->name)) {
                $firm->name = $firm->firm_name;
            }
        });

        static::creating(function (Firm $firm): void {
            if (! empty($firm->slug)) {
                return;
            }

            $base = Str::slug((string) ($firm->firm_name ?: $firm->name)) ?: 'firm';
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

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->withoutGlobalScopes();
    }
}

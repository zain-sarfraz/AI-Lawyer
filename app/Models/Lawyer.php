<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lawyer extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'user_id', 'title', 'phone', 'experience', 'bio', 'specialties', 'timezone', 'status', 'slug',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'status' => EntityStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lawyer $lawyer): void {
            if (! empty($lawyer->slug)) {
                return;
            }

            $name = User::query()->withoutGlobalScopes()->whereKey($lawyer->user_id)->value('name');
            $base = Str::slug((string) $name) ?: 'lawyer';
            $lawyer->slug = $base.'-'.$lawyer->user_id;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function scheduleBreaks(): HasMany
    {
        return $this->hasMany(ScheduleBreak::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest('id');
    }

    public function displayName(): string
    {
        return $this->user?->name ?? ('Lawyer #'.$this->id);
    }

    public function scopeActive($query)
    {
        return $query->where('status', EntityStatus::Active);
    }
}

<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lawyer extends Model
{
    protected $fillable = [
        'user_id', 'title', 'bio', 'specialties', 'timezone', 'status',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'status' => EntityStatus::class,
        ];
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
        return $this->hasMany(Appointment::class);
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

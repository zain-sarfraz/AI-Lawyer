<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'category_id', 'name', 'description', 'price', 'currency',
        'duration_minutes', 'buffer_minutes', 'booking_enabled', 'ai_enabled', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'booking_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'status' => EntityStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function lawyers(): BelongsToMany
    {
        return $this->belongsToMany(Lawyer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', EntityStatus::Active);
    }

    public function scopeAiVisible($query)
    {
        return $query->active()->where('ai_enabled', true);
    }

    public function scopeBookable($query)
    {
        return $query->active()->where('booking_enabled', true);
    }
}

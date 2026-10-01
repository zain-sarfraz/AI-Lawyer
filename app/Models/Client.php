<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
        ];
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'client_lead_links');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function notesRelation(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

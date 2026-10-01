<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'service_id', 'lawyer_id',
        'status', 'source', 'notes', 'last_contact_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'last_contact_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_lead_links');
    }

    public function notesRelation(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'name', 'client_number', 'phone', 'email', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client): void {
            if (filled($client->client_number)) {
                return;
            }

            $firmId = (int) ($client->firm_id ?: 0);
            $seq = static::query()->withoutGlobalScopes()->where('firm_id', $firmId)->count() + 1;
            $client->client_number = 'CL-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'client_lead_links');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest('id');
    }

    public function notesRelation(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

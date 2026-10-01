<?php

namespace App\Models;

use App\Enums\CallDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    protected $fillable = [
        'provider_call_id', 'direction', 'caller_phone', 'lawyer_id', 'lead_id',
        'client_id', 'started_at', 'ended_at', 'duration_seconds', 'status',
        'recording_url', 'transcript', 'summary', 'intent', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'direction' => CallDirection::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function aiSession(): HasOne
    {
        return $this->hasOne(AiSession::class);
    }
}

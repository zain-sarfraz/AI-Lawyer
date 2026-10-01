<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSession extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = [
        'firm_id', 'call_id', 'provider_session_id', 'started_at', 'ended_at',
        'status', 'audio_seconds', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata_json' => 'array',
            'audio_seconds' => 'integer',
        ];
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(AiToolCall::class);
    }
}

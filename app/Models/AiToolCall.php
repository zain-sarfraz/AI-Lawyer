<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolCall extends Model
{
    protected $fillable = [
        'ai_session_id', 'tool_name', 'arguments_json', 'result_json',
        'status', 'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'arguments_json' => 'array',
            'result_json' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    public function aiSession(): BelongsTo
    {
        return $this->belongsTo(AiSession::class);
    }
}

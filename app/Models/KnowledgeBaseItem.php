<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseItem extends Model
{
    protected $fillable = [
        'type', 'title', 'content', 'status', 'ai_visible',
    ];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'ai_visible' => 'boolean',
        ];
    }

    public function scopeAiVisible($query)
    {
        return $query->where('ai_visible', true)->where('status', EntityStatus::Active);
    }
}

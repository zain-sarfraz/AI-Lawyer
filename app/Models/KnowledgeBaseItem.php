<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseItem extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = [
        'firm_id', 'type', 'title', 'content', 'status', 'ai_visible',
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

<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = ['firm_id', 'date', 'name', 'status'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => EntityStatus::class,
        ];
    }
}

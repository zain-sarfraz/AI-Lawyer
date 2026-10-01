<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBreak extends Model
{
    protected $fillable = [
        'lawyer_id', 'day_of_week', 'start_time', 'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }
}

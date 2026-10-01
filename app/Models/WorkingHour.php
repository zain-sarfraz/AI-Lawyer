<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'lawyer_id', 'day_of_week', 'start_time', 'end_time', 'status',
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

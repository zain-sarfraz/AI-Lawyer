<?php

namespace App\Models;

use App\Enums\AvailabilityExceptionType;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityException extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'lawyer_id', 'date', 'start_time', 'end_time', 'type', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => AvailabilityExceptionType::class,
        ];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }
}

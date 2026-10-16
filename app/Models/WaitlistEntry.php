<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'lawyer_id', 'appointment_type_id', 'name', 'phone', 'email',
        'range_start', 'range_end', 'status', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'range_start' => 'date',
            'range_end' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }
}

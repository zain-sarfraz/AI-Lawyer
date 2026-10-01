<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'lawyer_id', 'client_id', 'lead_id', 'service_id',
        'start_at', 'end_at', 'status', 'source', 'external_event_id',
        'idempotency_key', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeBlocking($query)
    {
        return $query->whereIn('status', AppointmentStatus::blockingValues());
    }
}

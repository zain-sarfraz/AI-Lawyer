<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $fillable = [
        'firm_id', 'lawyer_id', 'client_id', 'lead_id', 'call_id', 'service_id', 'appointment_type_id',
        'rescheduled_from_id', 'start_at', 'end_at', 'status', 'source', 'external_event_id',
        'idempotency_key', 'notes', 'case_summary', 'fee_amount',
        'reminder_24h_sent_at', 'reminder_1h_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'fee_amount' => 'decimal:2',
            'reminder_24h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if ($status?->isBlocking() && $appointment->lawyer_id && $appointment->start_at) {
                $start = $appointment->start_at->copy()->utc()->format('Y-m-d H:i:s');
                $appointment->active_slot_key = $appointment->lawyer_id.':'.$start;

                return;
            }

            $appointment->active_slot_key = null;
        });
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

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Linked AI call, or the best matching call by lead / client / phone.
     */
    public function relatedCall(): ?Call
    {
        if ($this->relationLoaded('call') && $this->call) {
            return $this->call;
        }

        if ($this->call_id) {
            return Call::withoutGlobalScopes()->find($this->call_id);
        }

        $query = Call::withoutGlobalScopes()
            ->when($this->firm_id, fn ($q) => $q->where('firm_id', $this->firm_id))
            ->latest('started_at');

        if ($this->lead_id) {
            $call = (clone $query)->where('lead_id', $this->lead_id)->first();
            if ($call) {
                return $call;
            }
        }

        if ($this->client_id) {
            $call = (clone $query)->where('client_id', $this->client_id)->first();
            if ($call) {
                return $call;
            }
        }

        $phone = $this->lead?->phone ?: $this->client?->phone;
        if (! $phone) {
            return null;
        }

        $call = (clone $query)->where('caller_phone', $phone)->first();
        if ($call) {
            return $call;
        }

        $digits = substr((string) preg_replace('/\D/', '', $phone), -9);
        if (strlen($digits) >= 7) {
            return (clone $query)->where('caller_phone', 'like', "%{$digits}%")->first();
        }

        return null;
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function scopeBlocking($query)
    {
        return $query->whereIn('status', AppointmentStatus::blockingValues());
    }
}

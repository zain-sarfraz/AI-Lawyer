<?php

namespace App\Support;

use App\Models\Appointment;

final class BookingResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Appointment $appointment = null,
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly array $alternatives = [],
    ) {}

    public static function ok(Appointment $appointment): self
    {
        return new self(true, $appointment);
    }

    public static function conflict(string $message = 'Slot is no longer available.', array $alternatives = []): self
    {
        return new self(false, null, 'BOOKING_CONFLICT', $message, $alternatives);
    }

    public static function fail(string $code, string $message): self
    {
        return new self(false, null, $code, $message);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'code' => $this->code,
            'message' => $this->message,
            'appointment' => $this->appointment ? [
                'id' => $this->appointment->id,
                'lawyer_id' => $this->appointment->lawyer_id,
                'lead_id' => $this->appointment->lead_id,
                'client_id' => $this->appointment->client_id,
                'call_id' => $this->appointment->call_id,
                'service_id' => $this->appointment->service_id,
                'start_at' => $this->appointment->start_at?->toIso8601String(),
                'end_at' => $this->appointment->end_at?->toIso8601String(),
                'status' => $this->appointment->status?->value,
                'source' => $this->appointment->source,
            ] : null,
            'alternatives' => $this->alternatives,
        ];
    }
}

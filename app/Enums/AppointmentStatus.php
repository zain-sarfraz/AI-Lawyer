<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';

    public function isBlocking(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Confirmed,
        ], true);
    }

    public static function blockingValues(): array
    {
        return array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => $s->isBlocking())
        );
    }
}

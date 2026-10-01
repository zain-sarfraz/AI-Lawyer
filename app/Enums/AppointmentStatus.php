<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function isBlocking(): bool
    {
        return in_array($this, [
            self::Scheduled,
            self::Confirmed,
            self::InProgress,
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

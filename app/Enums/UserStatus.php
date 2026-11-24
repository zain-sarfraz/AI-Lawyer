<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingPayment = 'pending_payment';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';

    public function canAccessApp(): bool
    {
        return in_array($this, [self::Active, self::PastDue], true);
    }

    public function needsPayment(): bool
    {
        return $this === self::PendingPayment;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Models\Appointment;

class NullCalendarSyncProvider implements CalendarSyncProvider
{
    public function upsert(Appointment $appointment): void
    {
        // Calendar sync is stubbed until OAuth credentials are configured.
    }

    public function remove(Appointment $appointment): void
    {
        // Calendar sync is stubbed until OAuth credentials are configured.
    }
}

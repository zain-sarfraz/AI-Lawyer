<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Models\Appointment;

/**
 * External calendar sync is intentionally not implemented yet.
 *
 * Credentials to supply before the real providers are built:
 *
 * Google Calendar
 * - GOOGLE_CLIENT_ID
 * - GOOGLE_CLIENT_SECRET
 * - GOOGLE_REDIRECT_URI (for example https://your-host/oauth/google/callback)
 * - OAuth scope: https://www.googleapis.com/auth/calendar.events
 *
 * Microsoft Outlook / Graph
 * - MICROSOFT_CLIENT_ID
 * - MICROSOFT_CLIENT_SECRET
 * - MICROSOFT_TENANT_ID (or "common" for multi-tenant)
 * - MICROSOFT_REDIRECT_URI (for example https://your-host/oauth/microsoft/callback)
 * - OAuth scopes: offline_access Calendars.ReadWrite
 */
interface CalendarSyncProvider
{
    public function upsert(Appointment $appointment): void;

    public function remove(Appointment $appointment): void;
}

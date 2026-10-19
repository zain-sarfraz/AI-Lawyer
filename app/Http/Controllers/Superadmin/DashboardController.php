<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\LegalCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        // 1. Core Platform Counts
        $totalFirms = Firm::query()->count();
        $activeFirms = Firm::query()->where('status', 'active')->count();
        $suspendedFirms = Firm::query()->where('status', 'suspended')->count();

        $adminsQuery = User::query()
            ->withoutGlobalScopes()
            ->whereNotNull('firm_id')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Owner', 'Admin']))
            ->with(['roles', 'firm', 'lawyer' => fn ($q) => $q->withoutGlobalScopes()]);

        $firmAdminsCount = (clone $adminsQuery)->count();

        // 2. Platform Appointments Overview
        $allAppointments = Appointment::withoutGlobalScopes();
        $totalAppointments = (clone $allAppointments)->count();
        $aiBookingsCount = (clone $allAppointments)->where('source', 'phone_ai')->count();
        $staffBookingsCount = $totalAppointments - $aiBookingsCount;
        $todayAppointmentsCount = (clone $allAppointments)->whereDate('start_at', today())->count();
        $completedAppointmentsCount = (clone $allAppointments)->where('status', 'completed')->count();
        $scheduledAppointmentsCount = (clone $allAppointments)
            ->whereIn('status', ['confirmed', 'pending', 'scheduled'])
            ->where('start_at', '>=', now()->startOfDay())
            ->count();
        $cancelledAppointmentsCount = (clone $allAppointments)
            ->whereIn('status', ['cancelled', 'no_show'])
            ->count();
        $totalFeeAmount = (float) (clone $allAppointments)->sum('fee_amount');

        // 3. Firm Admins with Per-Admin Metrics & Values
        $admins = (clone $adminsQuery)->orderBy('name')->get();

        // Aggregate counts per firm to keep query count low
        $appointmentsByFirm = Appointment::withoutGlobalScopes()
            ->selectRaw('firm_id, status, source, count(*) as count, sum(coalesce(fee_amount, 0)) as total_fees')
            ->groupBy('firm_id', 'status', 'source')
            ->get();

        $lawyersCountByFirm = Lawyer::withoutGlobalScopes()
            ->selectRaw('firm_id, count(*) as count')
            ->groupBy('firm_id')
            ->pluck('count', 'firm_id');

        $casesCountByFirm = LegalCase::withoutGlobalScopes()
            ->selectRaw('firm_id, count(*) as count')
            ->groupBy('firm_id')
            ->pluck('count', 'firm_id');

        $clientsCountByFirm = Client::withoutGlobalScopes()
            ->selectRaw('firm_id, count(*) as count')
            ->groupBy('firm_id')
            ->pluck('count', 'firm_id');

        $adminMetrics = $admins->map(function (User $admin) use ($appointmentsByFirm, $lawyersCountByFirm, $casesCountByFirm, $clientsCountByFirm) {
            $firmId = $admin->firm_id;
            $firmAppts = $appointmentsByFirm->where('firm_id', $firmId);

            $getStatusVal = fn ($r) => $r->status instanceof AppointmentStatus ? $r->status->value : (string) ($r->status ?? '');

            $totalAppts = (int) $firmAppts->sum('count');
            $aiAppts = (int) $firmAppts->where('source', 'phone_ai')->sum('count');
            $staffAppts = $totalAppts - $aiAppts;
            $completedAppts = (int) $firmAppts->filter(fn ($r) => $getStatusVal($r) === 'completed')->sum('count');
            $scheduledAppts = (int) $firmAppts->filter(fn ($r) => in_array($getStatusVal($r), ['confirmed', 'pending', 'scheduled'], true))->sum('count');
            $cancelledAppts = (int) $firmAppts->filter(fn ($r) => in_array($getStatusVal($r), ['cancelled', 'no_show'], true))->sum('count');
            $totalFees = (float) $firmAppts->sum('total_fees');

            $roleName = $admin->getRoleNames()->first() ?? 'Admin';
            $statusVal = $admin->status?->value ?? (string) $admin->status;

            return [
                'user' => $admin,
                'admin_id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $roleName,
                'status' => $statusVal ?: 'active',
                'firm_id' => $firmId,
                'firm_name' => $admin->firm?->firm_name ?? 'Firm #'.$firmId,
                'firm_status' => $admin->firm?->status ?? 'active',
                'firm_timezone' => $admin->firm?->timezone ?? 'Asia/Karachi',
                'total_appointments' => $totalAppts,
                'ai_appointments' => $aiAppts,
                'ai_percentage' => $totalAppts > 0 ? round(($aiAppts / $totalAppts) * 100) : 0,
                'staff_appointments' => $staffAppts,
                'completed_appointments' => $completedAppts,
                'scheduled_appointments' => $scheduledAppts,
                'cancelled_appointments' => $cancelledAppts,
                'total_fees' => $totalFees,
                'lawyers_count' => (int) ($lawyersCountByFirm[$firmId] ?? 0),
                'cases_count' => (int) ($casesCountByFirm[$firmId] ?? 0),
                'clients_count' => (int) ($clientsCountByFirm[$firmId] ?? 0),
            ];
        });

        // 4. Graphs & Visual Analytics Data
        // A) Trend Chart: Past 14 Days (daily AI vs Staff vs Total)
        $daysRange = collect(range(13, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->toDateString());
        $apptsByDay = Appointment::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw('DATE(created_at) as date, source, count(*) as count')
            ->groupBy('date', 'source')
            ->get();

        $chartLabels = [];
        $aiTrend = [];
        $staffTrend = [];
        $totalTrend = [];

        foreach ($daysRange as $dateStr) {
            $formattedLabel = Carbon::parse($dateStr)->format('M d');
            $chartLabels[] = $formattedLabel;

            $dayRows = $apptsByDay->where('date', $dateStr);
            $aiCount = (int) ($dayRows->where('source', 'phone_ai')->first()->count ?? 0);
            $staffCount = (int) ($dayRows->where('source', '!=', 'phone_ai')->sum('count'));

            $aiTrend[] = $aiCount;
            $staffTrend[] = $staffCount;
            $totalTrend[] = $aiCount + $staffCount;
        }

        // B) Status Breakdown
        $statusRows = Appointment::withoutGlobalScopes()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $statusDataMap = [];
        foreach ($statusRows as $row) {
            $key = $row->status instanceof AppointmentStatus ? $row->status->value : (string) ($row->status ?? '');
            $statusDataMap[$key] = ($statusDataMap[$key] ?? 0) + (int) $row->count;
        }

        $statusScheduled = (int) (($statusDataMap['confirmed'] ?? 0) + ($statusDataMap['scheduled'] ?? 0));
        $statusCompleted = (int) ($statusDataMap['completed'] ?? 0);
        $statusPending = (int) ($statusDataMap['pending'] ?? 0);
        $statusCancelled = (int) (($statusDataMap['cancelled'] ?? 0) + ($statusDataMap['no_show'] ?? 0));
        $statusRescheduled = (int) ($statusDataMap['rescheduled'] ?? 0);

        // C) Top Admins Chart (Top 6 admins by appointment count)
        $topAdmins = $adminMetrics->sortByDesc('total_appointments')->take(6)->values();

        // 5. Appointments List for Explorer (up to 75 latest appointments)
        $appointments = Appointment::withoutGlobalScopes()
            ->with([
                'firm',
                'lawyer' => fn ($q) => $q->withoutGlobalScopes(),
                'lawyer.user' => fn ($q) => $q->withoutGlobalScopes(),
                'service' => fn ($q) => $q->withoutGlobalScopes(),
                'lead' => fn ($q) => $q->withoutGlobalScopes(),
                'client' => fn ($q) => $q->withoutGlobalScopes(),
                'call' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->latest('start_at')
            ->latest('id')
            ->limit(75)
            ->get();

        $firmAdminLookup = $admins->groupBy('firm_id')->map(function ($group) {
            return $group->map(fn ($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ]);
        });

        $serializedAppointments = $appointments->map(function ($appt) use ($firmAdminLookup) {
            $firmAdmins = $firmAdminLookup->get($appt->firm_id);
            $primaryAdmin = $firmAdmins ? $firmAdmins->first() : null;
            $adminName = $primaryAdmin['name'] ?? 'Firm Admin';
            $adminId = $primaryAdmin['id'] ?? 0;

            $startAt = $appt->start_at;
            $dateFormatted = $startAt ? $startAt->format('M j, Y') : '—';
            $timeFormatted = $startAt ? $startAt->format('g:i A') : '—';
            $relativeBadge = $startAt ? ($startAt->isToday() ? 'Today' : ($startAt->isTomorrow() ? 'Tomorrow' : ($startAt->isPast() ? 'Past' : 'Upcoming'))) : '';

            $clientName = $appt->client?->name ?: ($appt->lead?->name ?: 'Caller');
            $clientPhone = $appt->client?->phone ?: ($appt->lead?->phone ?: '—');
            $lawyerName = $appt->lawyer?->displayName() ?? 'Unassigned';
            $serviceName = $appt->service?->name ?? 'General Consultation';
            $status = $appt->status?->value ?? (string) $appt->status;

            return [
                'id' => $appt->id,
                'firm_id' => (int) $appt->firm_id,
                'firm_name' => $appt->firm?->firm_name ?? 'Firm #'.$appt->firm_id,
                'admin_id' => (int) $adminId,
                'admin_name' => $adminName,
                'date' => $dateFormatted,
                'time' => $timeFormatted,
                'relative' => $relativeBadge,
                'client_name' => $clientName,
                'client_phone' => $clientPhone,
                'lawyer_name' => $lawyerName,
                'service_name' => $serviceName,
                'source' => $appt->source ?: 'manual',
                'is_ai' => $appt->source === 'phone_ai',
                'status' => $status,
                'fee' => $appt->fee_amount ? '$'.number_format((float) $appt->fee_amount, 2) : '—',
                'notes' => $appt->notes ?: 'No consultation notes recorded.',
                'case_summary' => $appt->case_summary ?: 'No case summary recorded.',
                'call_intent' => $appt->call?->intent,
                'call_outcome' => $appt->call?->outcome,
                'call_duration' => $appt->call?->duration_seconds ? gmdate('i:s', $appt->call->duration_seconds) : null,
                'created_at' => $appt->created_at?->format('M j, Y g:i A') ?? '—',
            ];
        })->values();

        return view('superadmin.dashboard', [
            'stats' => [
                'firms' => $totalFirms,
                'active_firms' => $activeFirms,
                'suspended_firms' => $suspendedFirms,
                'firm_admins' => $firmAdminsCount,
                'total_appointments' => $totalAppointments,
                'today_appointments' => $todayAppointmentsCount,
                'ai_bookings' => $aiBookingsCount,
                'ai_share' => $totalAppointments > 0 ? round(($aiBookingsCount / $totalAppointments) * 100) : 0,
                'completed_appointments' => $completedAppointmentsCount,
                'scheduled_appointments' => $scheduledAppointmentsCount,
                'cancelled_appointments' => $cancelledAppointmentsCount,
                'total_fees' => $totalFeeAmount,
            ],
            'adminMetrics' => $adminMetrics,
            'recentFirms' => Firm::query()->latest('id')->limit(6)->get(),
            'chartData' => [
                'trend' => [
                    'labels' => $chartLabels,
                    'ai' => $aiTrend,
                    'staff' => $staffTrend,
                    'total' => $totalTrend,
                ],
                'status' => [
                    'labels' => ['Confirmed / Scheduled', 'Completed', 'Pending', 'Cancelled / No-show', 'Rescheduled'],
                    'values' => [$statusScheduled, $statusCompleted, $statusPending, $statusCancelled, $statusRescheduled],
                ],
                'admins' => [
                    'labels' => $topAdmins->map(fn ($m) => $m['name'].' ('.$m['firm_name'].')')->toArray(),
                    'ai' => $topAdmins->pluck('ai_appointments')->toArray(),
                    'staff' => $topAdmins->pluck('staff_appointments')->toArray(),
                ],
            ],
            'appointments' => $appointments,
            'serializedAppointments' => $serializedAppointments,
            'firmAdminLookup' => $firmAdminLookup,
        ]);
    }
}


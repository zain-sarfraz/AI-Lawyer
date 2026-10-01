<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Integrations\FineTunerService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Lead;
use App\Enums\AppointmentStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(FineTunerService $fineTuner): View
    {
        $today = now()->toDateString();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', auth()->user()->name ?? 'there')[0];

        $aiBookingsToday = Appointment::query()
            ->whereDate('created_at', $today)
            ->where('source', 'phone_ai')
            ->count();

        $handoffsToday = Call::query()
            ->whereDate('started_at', $today)
            ->where('outcome', 'transferred')
            ->count();

        $pendingFollowUps = CallbackRequest::query()
            ->where('status', 'open')
            ->count()
            + Lead::query()->where('status', 'follow_up')->count();

        return view('admin.dashboard', [
            'greeting' => "{$greeting}, {$firstName}.",
            'stats' => [
                'leads_today' => Lead::query()->whereDate('created_at', $today)->count(),
                'appointments_today' => Appointment::query()->whereDate('start_at', $today)->blocking()->count(),
                'calls_today' => Call::query()->whereDate('started_at', $today)->count(),
                'ai_bookings' => $aiBookingsToday,
                'pending_followups' => $pendingFollowUps,
                'handoffs_today' => $handoffsToday,
                'open_leads' => Lead::query()->whereNotIn('status', ['converted', 'lost'])->count(),
            ],
            'todaysAppointments' => Appointment::query()
                ->with(['lawyer.user', 'service', 'lead'])
                ->whereDate('start_at', $today)
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->orderBy('start_at')
                ->limit(8)
                ->get(),
            'recentCalls' => Call::query()->latest('started_at')->limit(8)->get(),
            'recentLeads' => Lead::query()->with(['service', 'lawyer.user'])->latest()->limit(6)->get(),
            'openCallbacks' => CallbackRequest::query()->where('status', 'open')->latest()->limit(5)->get(),
            'fineTuner' => $fineTuner->snapshot(),
        ]);
    }
}

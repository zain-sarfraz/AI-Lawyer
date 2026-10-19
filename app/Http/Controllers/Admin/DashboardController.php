<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Integrations\FineTunerService;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Client;
use App\Models\CrmDocument;
use App\Models\Lead;
use App\Models\LegalCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(FineTunerService $fineTuner): View
    {
        $user = auth()->user()?->loadMissing('firm', 'lawyer');
        $hour = (int) now()->format('G');
        $partOfDay = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firmLabel = $user?->firm?->firm_name ?: 'Firm';
        $firmShort = str($firmLabel)->before(' ')->toString() ?: 'Firm';

        // Lawyer / Staff: personal desk (appointments + records), not the full admin ops board.
        if ($user && ! $user->isAdmin()) {
            return $this->teamDashboard($user, $partOfDay, $firmShort);
        }

        $today = now()->toDateString();
        $firmId = (int) ($user?->firm_id ?? 0);

        $stats = Cache::remember("dashboard.stats.{$firmId}.{$today}", 30, function () use ($today) {
            return [
                'leads_today' => Lead::query()->whereDate('created_at', $today)->count(),
                'appointments_today' => Appointment::query()->whereDate('start_at', $today)->blocking()->count(),
                'calls_today' => Call::query()->whereDate('started_at', $today)->count(),
                'ai_bookings' => Appointment::query()
                    ->whereDate('created_at', $today)
                    ->where('source', 'phone_ai')
                    ->count(),
                'pending_followups' => CallbackRequest::query()->where('status', 'open')->count()
                    + Lead::query()->where('status', 'follow_up')->count(),
                'handoffs_today' => Call::query()
                    ->whereDate('started_at', $today)
                    ->where('outcome', 'transferred')
                    ->count(),
                'open_leads' => Lead::query()->whereNotIn('status', ['converted', 'lost'])->count(),
            ];
        });

        return view('admin.dashboard', [
            'greeting' => "{$partOfDay}, {$firmShort}.",
            'stats' => $stats,
            'todaysAppointments' => Appointment::query()
                ->with(['lawyer.user', 'service', 'lead', 'client'])
                ->whereDate('start_at', $today)
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->latest('id')
                ->limit(8)
                ->get(),
            'recentCalls' => Call::query()->latest('started_at')->limit(8)->get(),
            'recentLeads' => Lead::query()->with(['service', 'lawyer.user'])->latest()->limit(6)->get(),
            'openCallbacks' => CallbackRequest::query()->where('status', 'open')->latest()->limit(5)->get(),
            'fineTuner' => $fineTuner->localStatus(),
        ]);
    }

    private function teamDashboard($user, string $partOfDay, string $firmShort): View
    {
        $upcoming = Appointment::query()
            ->with(['lawyer.user', 'client', 'lead', 'service', 'appointmentType'])
            ->where('start_at', '>=', now()->subHour())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->latest('id')
            ->limit(12)
            ->get();

        $todayCount = Appointment::query()
            ->whereDate('start_at', now()->toDateString())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->count();

        $clients = Client::query()->latest('id')->limit(6)->get();
        $documents = CrmDocument::query()->with(['client'])->latest('id')->limit(6)->get();
        $cases = LegalCase::query()->with(['client', 'lawyer.user'])->latest('id')->limit(6)->get();

        $nextAppointment = $upcoming->first();

        $startOfWeek = now()->startOfWeek();
        $todayDate = now()->toDateString();
        $weekDays = collect(range(0, 6))->map(function ($dayOffset) use ($startOfWeek, $todayDate) {
            $d = (clone $startOfWeek)->addDays($dayOffset);

            return [
                'name' => $d->format('D'),
                'number' => $d->format('j'),
                'date' => $d->toDateString(),
                'is_today' => $d->toDateString() === $todayDate,
            ];
        });

        return view('admin.dashboard-user', [
            'greeting' => "{$partOfDay}, {$user->name}.",
            'firmShort' => $firmShort,
            'roleLabel' => $user->getRoleNames()->first() ?: 'Team',
            'upcomingAppointments' => $upcoming,
            'nextAppointment' => $nextAppointment,
            'weekDays' => $weekDays,
            'todayCount' => $todayCount,
            'clients' => $clients,
            'documents' => $documents,
            'cases' => $cases,
        ]);
    }
}

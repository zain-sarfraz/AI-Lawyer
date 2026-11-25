<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Integrations\FineTunerService;
use App\Domain\Scheduling\FirmTime;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Client;
use App\Models\CrmDocument;
use App\Models\Lead;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(FineTunerService $fineTuner): View|RedirectResponse
    {
        $user = auth()->user()?->loadMissing('firm', 'lawyer');
        $hour = (int) now()->format('G');
        $partOfDay = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firmLabel = $user?->firm?->firm_name ?: 'Firm';
        $firmShort = str($firmLabel)->before(' ')->toString() ?: 'Firm';

        // Firm admins use the dedicated firm management dashboard.
        if ($user?->isFirmAdmin() && $user->firm?->isFirmAccount()) {
            return redirect()->route('firm.dashboard');
        }

        // Individual / firm lawyers: personal desk — leave this path unchanged.
        if ($user && ! $user->isAdmin()) {
            return $this->teamDashboard($user, $partOfDay, $firmShort);
        }

        $firmTime = app(FirmTime::class);
        $firm = $user?->firm;
        $localNow = $firmTime->now($firm);
        $today = $localNow->toDateString();
        $todayBounds = $firmTime->utcDayBounds($today, $firm);
        $firmId = (int) ($user?->firm_id ?? 0);

        $stats = Cache::remember("dashboard.stats.{$firmId}.{$today}", 30, function () use ($today, $todayBounds) {
            return [
                'leads_today' => Lead::query()->whereDate('created_at', $today)->count(),
                'appointments_today' => Appointment::query()->whereBetween('start_at', $todayBounds)->blocking()->count(),
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
                ->with(['firm', 'lawyer.user', 'service', 'lead', 'client'])
                ->whereBetween('start_at', $todayBounds)
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
        $firmTime = app(FirmTime::class);
        $firm = $user?->firm;
        $localNow = $firmTime->now($firm);
        $todayDate = $localNow->toDateString();
        $todayBounds = $firmTime->utcDayBounds($todayDate, $firm);

        $upcoming = Appointment::query()
            ->with(['firm', 'lawyer.user', 'client', 'lead', 'service', 'appointmentType'])
            ->where('start_at', '>=', now()->subHour())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->latest('id')
            ->limit(12)
            ->get();

        $todayCount = Appointment::query()
            ->whereBetween('start_at', $todayBounds)
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->count();

        $clients = Client::query()->latest('id')->limit(6)->get();
        $documents = CrmDocument::query()->with(['client'])->latest('id')->limit(6)->get();
        $cases = LegalCase::query()->with(['client', 'lawyer.user'])->latest('id')->limit(6)->get();

        $nextAppointment = $upcoming->first();

        $startOfWeek = $localNow->copy()->startOfWeek();
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
            'isIndividual' => $user->isIndividual(),
            'roleLabel' => $user->displayRoleLabel(),
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

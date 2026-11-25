<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Display platform-wide appointments across all firms and solo lawyers.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('platform.dashboard.view'), 403);

        $query = Appointment::withoutGlobalScopes()
            ->with([
                'firm',
                'lawyer' => fn ($q) => $q->withoutGlobalScopes(),
                'lawyer.user' => fn ($q) => $q->withoutGlobalScopes(),
                'client' => fn ($q) => $q->withoutGlobalScopes(),
                'lead' => fn ($q) => $q->withoutGlobalScopes(),
                'service' => fn ($q) => $q->withoutGlobalScopes(),
            ]);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhere('case_summary', 'like', "%{$search}%")
                    ->orWhereHas('client', function (Builder $cq) use ($search): void {
                        $cq->withoutGlobalScopes()
                            ->where(fn (Builder $inner) => $inner
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('lead', function (Builder $lq) use ($search): void {
                        $lq->withoutGlobalScopes()
                            ->where(fn (Builder $inner) => $inner
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('lawyer.user', function (Builder $uq) use ($search): void {
                        $uq->withoutGlobalScopes()
                            ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function (Builder $sq) use ($search): void {
                        $sq->withoutGlobalScopes()
                            ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('firm', function (Builder $fq) use ($search): void {
                        $fq->where('firm_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($accountType = trim((string) $request->input('account_type', ''))) {
            if ($accountType === 'individual') {
                $this->applyIndividualScope($query);
            } elseif ($accountType === 'firm') {
                $this->applyFirmScope($query);
            }
        }

        if ($firmFilter = trim((string) $request->input('firm_id', ''))) {
            if ($firmFilter === 'individual') {
                $this->applyIndividualScope($query);
            } elseif (ctype_digit($firmFilter)) {
                $query->where('firm_id', (int) $firmFilter);
            }
        }

        if ($lawyerId = trim((string) $request->input('lawyer_id', ''))) {
            if (ctype_digit($lawyerId)) {
                $query->where('lawyer_id', (int) $lawyerId);
            }
        }

        if ($serviceId = trim((string) $request->input('service_id', ''))) {
            if (ctype_digit($serviceId)) {
                $query->where('service_id', (int) $serviceId);
            }
        }

        if ($status = trim((string) $request->input('status', ''))) {
            if ($status === 'active') {
                $query->whereIn('status', AppointmentStatus::blockingValues());
            } else {
                $query->where('status', $status);
            }
        }

        if ($source = trim((string) $request->input('source', ''))) {
            if ($source === 'phone_ai') {
                $query->where('source', 'phone_ai');
            } elseif ($source === 'manual') {
                $query->where(function (Builder $q): void {
                    $q->whereNull('source')->orWhere('source', '!=', 'phone_ai');
                });
            }
        }

        $this->applyDateFilters(
            $query,
            trim((string) $request->input('date_preset', '')),
            trim((string) $request->input('date_from', '')),
            trim((string) $request->input('date_to', '')),
        );

        match (trim((string) $request->input('sort', 'latest'))) {
            'oldest' => $query->oldest('start_at')->oldest('id'),
            'newest_created' => $query->latest('created_at')->latest('id'),
            'highest_fee' => $query->orderByDesc('fee_amount')->latest('start_at'),
            'lowest_fee' => $query->orderBy('fee_amount')->latest('start_at'),
            default => $query->latest('start_at')->latest('id'),
        };

        $appointments = $query
            ->paginate(12)
            ->withQueryString();

        $baseStats = Appointment::withoutGlobalScopes();
        $totalAppointments = (clone $baseStats)->count();
        $aiBookingsCount = (clone $baseStats)->where('source', 'phone_ai')->count();
        $upcomingCount = (clone $baseStats)
            ->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::Pending->value])
            ->count();
        $completedCount = (clone $baseStats)->where('status', AppointmentStatus::Completed->value)->count();
        $totalFeeAmount = (float) (clone $baseStats)->sum('fee_amount');

        $firms = Firm::query()->lawFirms()->orderBy('firm_name')->get(['id', 'firm_name']);
        $lawyers = Lawyer::withoutGlobalScopes()
            ->with([
                'user' => fn ($q) => $q->withoutGlobalScopes()->select(['id', 'name', 'account_type', 'role']),
                'firm:id,firm_name,account_type,plan',
            ])
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Lawyer $l) => strtolower($l->displayName()))
            ->values();
        $services = Service::withoutGlobalScopes()
            ->with('firm:id,firm_name,account_type,plan')
            ->orderBy('name')
            ->get(['id', 'firm_id', 'name']);

        return view('superadmin.appointments.index', [
            'appointments' => $appointments,
            'firms' => $firms,
            'lawyers' => $lawyers,
            'services' => $services,
            'stats' => [
                'total' => $totalAppointments,
                'ai' => $aiBookingsCount,
                'ai_pct' => $totalAppointments > 0 ? (int) round(($aiBookingsCount / $totalAppointments) * 100) : 0,
                'upcoming' => $upcomingCount,
                'completed' => $completedCount,
                'fees' => $totalFeeAmount,
            ],
        ]);
    }

    private function applyIndividualScope(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->whereNull('firm_id')
                ->orWhereHas('firm', fn (Builder $fq) => $fq->where('account_type', 'individual')->orWhere('plan', 'starter'))
                ->orWhereHas('lawyer.user', fn (Builder $uq) => $uq->withoutGlobalScopes()->where(fn (Builder $iq) => $iq->where('account_type', 'individual')->orWhere('role', 'individual_lawyer')));
        });
    }

    private function applyFirmScope(Builder $query): void
    {
        $query->whereNotNull('firm_id')
            ->whereHas('firm', fn (Builder $fq) => $fq->lawFirms())
            ->whereDoesntHave('lawyer.user', fn (Builder $uq) => $uq->withoutGlobalScopes()->where(fn (Builder $iq) => $iq->where('account_type', 'individual')->orWhere('role', 'individual_lawyer')));
    }

    private function applyDateFilters(Builder $query, string $preset, string $dateFrom, string $dateTo): void
    {
        $now = Carbon::now();

        match ($preset) {
            'today' => $query->whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]),
            'tomorrow' => $query->whereBetween('start_at', [$now->copy()->addDay()->startOfDay(), $now->copy()->addDay()->endOfDay()]),
            'this_week' => $query->whereBetween('start_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'this_month' => $query->whereBetween('start_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            'upcoming' => $query->where('start_at', '>=', $now),
            'past' => $query->where('start_at', '<', $now),
            default => null,
        };

        if ($dateFrom !== '') {
            try {
                $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
                $query->where('start_at', '>=', $from);
            } catch (\Throwable) {
            }
        }

        if ($dateTo !== '') {
            try {
                $to = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
                $query->where('start_at', '<=', $to);
            } catch (\Throwable) {
            }
        }
    }
}

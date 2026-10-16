<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Firm\FirmContext;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\Lawyer;
use App\Models\ScheduleBreak;
use App\Models\Service;
use App\Models\WorkingHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:schedules.manage', only: ['storeWorkingHour', 'storeHoliday']),
        ];
    }

    public function index(Request $request): View
    {
        abort_unless(
            auth()->user()?->can('schedules.manage') || auth()->user()?->can('appointments.view'),
            403,
        );

        $lawyers = Lawyer::query()->with('user')->active()->orderBy('id')->get();
        $services = Service::query()->active()->orderBy('name')->get();

        $tones = ['mint', 'blue', 'violet', 'amber', 'rose'];
        // Show every appointment (pending, completed, cancelled, …) with its status.
        $appointments = Appointment::query()
            ->with(['lawyer.user', 'service', 'client', 'lead'])
            ->where('start_at', '>=', now()->subMonths(2)->startOfDay())
            ->where('start_at', '<=', now()->addMonths(4)->endOfDay())
            ->orderBy('start_at')
            ->get()
            ->map(function (Appointment $appointment) use ($tones) {
                $status = $appointment->status?->value ?? (string) $appointment->status;

                return [
                    'id' => $appointment->id,
                    'title' => $appointment->client?->name
                        ?: $appointment->lead?->name
                        ?: $appointment->lead?->phone
                        ?: 'Appointment',
                    'start' => $appointment->start_at?->toIso8601String(),
                    'end' => $appointment->end_at?->toIso8601String(),
                    'date' => $appointment->start_at?->toDateString(),
                    'time' => $appointment->start_at?->format('g:i A'),
                    'status' => $status,
                    'status_label' => str_replace('_', ' ', $status),
                    'service_id' => $appointment->service_id,
                    'service' => $appointment->service?->name,
                    'lawyer_id' => $appointment->lawyer_id,
                    'lawyer' => $appointment->lawyer?->displayName(),
                    'tone' => $tones[(int) $appointment->lawyer_id % count($tones)],
                    'is_completed' => $status === AppointmentStatus::Completed->value,
                ];
            })
            ->values();

        return view('admin.schedule.index', [
            'lawyers' => $lawyers,
            'services' => $services,
            'appointments' => $appointments,
            'workingHours' => WorkingHour::query()->with('lawyer.user')->orderBy('day_of_week')->get(),
            'breaks' => ScheduleBreak::query()->with('lawyer.user')->orderBy('day_of_week')->get(),
            'holidays' => Holiday::query()->orderBy('date')->get(),
            'filters' => [
                'view' => $request->string('view')->toString() ?: 'week',
                'date' => $request->string('date')->toString() ?: now()->toDateString(),
                'service_id' => $request->input('service_id'),
                'lawyer_id' => $request->input('lawyer_id'),
            ],
        ]);
    }

    public function storeWorkingHour(Request $request): RedirectResponse
    {
        $this->authorize('create', WorkingHour::class);

        $data = $request->validate([
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required'],
            'end_time' => ['required'],
        ]);
        $restrictedLawyerId = app(FirmContext::class)->restrictedLawyerId();
        if ($restrictedLawyerId !== null) {
            $data['lawyer_id'] = $restrictedLawyerId;
        }
        WorkingHour::query()->create($data + ['status' => 'active']);

        return back()->with('success', 'Working hours saved.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorize('create', Holiday::class);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        Holiday::query()->updateOrCreate(
            ['firm_id' => app(FirmContext::class)->id(), 'date' => $data['date']],
            $data + ['status' => 'active'],
        );

        return back()->with('success', 'Holiday saved.');
    }
}

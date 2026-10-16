<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\Appointments\ChangeAppointmentStatus;
use App\Domain\Firm\FirmContext;
use App\Domain\Scheduling\AvailabilityService;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\CallTranscript;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AppointmentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AvailabilityService $availability,
        private readonly ChangeAppointmentStatus $statusChange,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:appointments.view', only: ['index', 'availability', 'updateNotes']),
            new Middleware('permission:appointments.manage', only: ['create', 'store', 'update', 'cancel', 'complete', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);

        $statusFilter = $request->string('status')->toString();

        $appointments = Appointment::query()
            ->with(['lawyer.user', 'service', 'lead', 'client', 'call'])
            ->when($request->date, fn ($q) => $q->whereDate('start_at', $request->date))
            ->when(
                $statusFilter !== '' && $statusFilter !== 'all',
                fn ($q) => $q->where('status', $statusFilter)
            )
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $relatedCalls = $this->resolveRelatedCalls($appointments->getCollection());

        return view('appointments.index', [
            'appointments' => $appointments,
            'relatedCalls' => $relatedCalls,
            'statusFilter' => $statusFilter !== '' ? $statusFilter : 'all',
            'formServices' => Service::query()->bookable()->orderBy('name')->get(),
            'formLawyers' => Lawyer::query()->active()->with('user')->get(),
            'formClients' => \App\Models\Client::query()->orderBy('name')->get(['id', 'name', 'client_number']),
            'canManageCases' => auth()->user()?->can('cases.manage') ?? false,
            'canManageAppointments' => auth()->user()?->can('appointments.manage') ?? false,
        ]);
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return Collection<int, Call>
     */
    private function resolveRelatedCalls(Collection $appointments): Collection
    {
        $byAppointment = collect();

        foreach ($appointments as $appointment) {
            if ($appointment->call) {
                $byAppointment->put($appointment->id, $appointment->call);
            }
        }

        $missing = $appointments->reject(fn (Appointment $a) => $byAppointment->has($a->id));
        if ($missing->isEmpty()) {
            return $byAppointment;
        }

        $leadIds = $missing->pluck('lead_id')->filter()->unique()->values();
        $clientIds = $missing->pluck('client_id')->filter()->unique()->values();
        $phones = $missing
            ->map(fn (Appointment $a) => $a->lead?->phone ?: $a->client?->phone)
            ->filter()
            ->unique()
            ->values();

        $candidates = Call::query()
            ->when(
                $leadIds->isNotEmpty() || $clientIds->isNotEmpty() || $phones->isNotEmpty(),
                fn ($q) => $q->where(function ($inner) use ($leadIds, $clientIds, $phones) {
                    if ($leadIds->isNotEmpty()) {
                        $inner->orWhereIn('lead_id', $leadIds);
                    }
                    if ($clientIds->isNotEmpty()) {
                        $inner->orWhereIn('client_id', $clientIds);
                    }
                    if ($phones->isNotEmpty()) {
                        $inner->orWhereIn('caller_phone', $phones);
                    }
                }),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->latest('started_at')
            ->get();

        foreach ($missing as $appointment) {
            $phone = $appointment->lead?->phone ?: $appointment->client?->phone;
            $phoneDigits = $phone ? substr((string) preg_replace('/\D/', '', $phone), -9) : '';

            $match = $candidates->first(function (Call $call) use ($appointment, $phone, $phoneDigits) {
                if ($appointment->lead_id && $call->lead_id === $appointment->lead_id) {
                    return true;
                }
                if ($appointment->client_id && $call->client_id === $appointment->client_id) {
                    return true;
                }
                // Only match by phone if the call was created around the same time (within 24 hours)
                $timeDiffHours = $appointment->created_at && $call->started_at
                    ? abs($appointment->created_at->diffInHours($call->started_at))
                    : 999;
                if ($timeDiffHours <= 24) {
                    if ($phone && $call->caller_phone === $phone) {
                        return true;
                    }
                    if ($phoneDigits && strlen($phoneDigits) >= 7) {
                        $callDigits = substr((string) preg_replace('/\D/', '', (string) $call->caller_phone), -9);
                        if ($callDigits === $phoneDigits) {
                            return true;
                        }
                    }
                }

                return false;
            });

            if ($match) {
                $byAppointment->put($appointment->id, $match);
            }
        }

        return $byAppointment;
    }

    /**
     * @return array<string, mixed>
     */
    public static function detailPayload(Appointment $appointment, ?Call $call = null): array
    {
        $call ??= $appointment->relatedCall();
        $status = $appointment->status?->value ?? (string) $appointment->status;
        $name = $appointment->client?->name
            ?: $appointment->lead?->name
            ?: $appointment->lead?->phone
            ?: '—';

        $firm = $appointment->firm ?? app(\App\Domain\Firm\FirmService::class)->current();
        $firmTime = app(\App\Domain\Scheduling\FirmTime::class);
        $localStart = $appointment->start_at ? $firmTime->toFirm($appointment->start_at, $firm) : null;

        return [
            'id' => $appointment->id,
            'name' => $name,
            'client_id' => $appointment->client_id,
            'lawyer_id' => $appointment->lawyer_id,
            'phone' => $appointment->lead?->phone ?: $appointment->client?->phone ?: $call?->caller_phone,
            'service' => $appointment->service?->name,
            'lawyer' => $appointment->lawyer?->displayName(),
            'source' => $appointment->source === 'phone_ai' ? 'AI booked' : ($appointment->source ?: 'staff'),
            'date' => $localStart?->format('Y-m-d') ?: $appointment->start_at?->format('Y-m-d'),
            'time' => $localStart?->format('g:i A') ?: $appointment->start_at?->format('g:i A'),
            'when' => $localStart?->format('D, M j, Y · g:i A') ?: $appointment->start_at?->format('D, M j, Y · g:i A'),
            'status' => str_replace('_', ' ', $status),
            'status_value' => $status,
            'email' => $appointment->lead?->email ?: $appointment->client?->email,
            'service_id' => $appointment->service_id,
            'start_at' => $appointment->start_at?->toIso8601String(),
            'notes' => (string) ($appointment->notes ?? ''),
            'notes_url' => route('appointments.notes', $appointment),
            'update_url' => route('appointments.update', $appointment),
            'can_edit' => auth()->user()?->can('appointments.manage') ?? false,
            'can_edit_notes' => auth()->user()?->can('appointments.view') ?? false,
            'case_title' => trim(($name !== '—' ? $name.' — ' : '').($appointment->service?->name ?: 'Consultation')),
            'summary' => $call?->summary,
            'recording_url' => $call?->recording_url,
            'transcript_lines' => CallTranscript::toLines($call?->transcript),
            'has_call' => (bool) $call,
            'call_status' => $call?->status,
            'call_duration' => $call?->duration_seconds
                ? gmdate('i:s', $call->duration_seconds)
                : null,
        ];
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Appointment::class);

        return redirect()->route('appointments.index', ['open' => 'create-appointment']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'lawyer_id' => ['required', 'exists:lawyers,id'],
            'start_at' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['source'] = 'staff';
        $restrictedLawyerId = app(FirmContext::class)->restrictedLawyerId();
        if ($restrictedLawyerId !== null) {
            $data['lawyer_id'] = $restrictedLawyerId;
        }
        $result = $this->booking->book($data);
        if (! $result->success) {
            return back()->withInput()->withErrors(['start_at' => $result->message ?? 'Booking conflict.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'lawyer_id' => ['required', 'exists:lawyers,id'],
            'start_at' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,confirmed,completed,no_show,cancelled'],
        ]);

        $restrictedLawyerId = app(FirmContext::class)->restrictedLawyerId();
        if ($restrictedLawyerId !== null) {
            $data['lawyer_id'] = $restrictedLawyerId;
        }

        $result = $this->booking->update($appointment, $data);
        if (! $result->success) {
            return back()->withInput()->withErrors(['start_at' => $result->message ?? 'Unable to update appointment.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $this->booking->cancel($appointment, 'Cancelled by staff');

        return back()->with('success', 'Appointment cancelled.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $this->statusChange->execute($appointment, AppointmentStatus::Completed);

        return back()->with('success', 'Appointment marked complete.');
    }

    public function updateNotes(Request $request, Appointment $appointment): RedirectResponse
    {
        // Lawyers may edit notes on their appointments without booking rights.
        $this->authorize('view', $appointment);

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Appointment notes saved.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return back()->with('success', 'Appointment deleted.');
    }

    public function availability(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'date' => ['required', 'date'],
        ]);
        $service = Service::query()->findOrFail($data['service_id']);
        $lawyer = ! empty($data['lawyer_id']) ? Lawyer::query()->find($data['lawyer_id']) : null;

        return response()->json([
            'slots' => $this->availability->findSlots($service, \Carbon\Carbon::parse($data['date']), $lawyer),
        ]);
    }
}

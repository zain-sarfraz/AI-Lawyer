<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\Scheduling\AvailabilityService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AvailabilityService $availability,
    ) {}

    public function index(Request $request): View
    {
        $appointments = Appointment::query()
            ->with(['lawyer.user', 'service', 'lead', 'client'])
            ->when($request->date, fn ($q) => $q->whereDate('start_at', $request->date))
            ->latest('start_at')
            ->paginate(25);

        return view('appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        return view('appointments.form', [
            'services' => Service::query()->bookable()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
        $result = $this->booking->book($data);
        if (! $result->success) {
            return back()->withInput()->withErrors(['start_at' => $result->message ?? 'Booking conflict.']);
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->booking->cancel($appointment, 'Cancelled by staff');

        return back()->with('success', 'Appointment cancelled.');
    }

    public function availability(Request $request)
    {
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

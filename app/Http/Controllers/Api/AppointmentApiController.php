<?php

namespace App\Http\Controllers\Api;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\Scheduling\AvailabilityService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentApiController extends Controller
{
    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AvailabilityService $availability,
    ) {}

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'date' => ['required', 'date'],
        ]);
        $service = Service::query()->findOrFail($data['service_id']);
        $lawyer = isset($data['lawyer_id']) ? Lawyer::query()->find($data['lawyer_id']) : null;

        return response()->json([
            'data' => $this->availability->findSlots($service, Carbon::parse($data['date']), $lawyer),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'lawyer_id' => ['required', 'exists:lawyers,id'],
            'start_at' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->booking->book($data);
        $status = $result->success ? 201 : ($result->code === 'BOOKING_CONFLICT' ? 409 : 422);

        return response()->json($result->toArray(), $status);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate([
            'start_at' => ['required', 'date'],
        ]);
        $result = $this->booking->reschedule($appointment, $data['start_at']);
        $status = $result->success ? 200 : 409;

        return response()->json($result->toArray(), $status);
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string']]);
        $cancelled = $this->booking->cancel($appointment, $data['reason'] ?? null);

        return response()->json([
            'success' => true,
            'appointment' => $cancelled,
        ]);
    }
}

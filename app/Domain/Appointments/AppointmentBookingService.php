<?php

namespace App\Domain\Appointments;

use App\Domain\Audit\AuditLogService;
use App\Domain\CRM\LeadService;
use App\Domain\Firm\FirmService;
use App\Domain\Notifications\NotificationService;
use App\Domain\Scheduling\AvailabilityService;
use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\BookingResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentBookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly LeadService $leads,
        private readonly FirmService $firmService,
        private readonly AuditLogService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function book(array $payload): BookingResult
    {
        $service = Service::query()->find($payload['service_id'] ?? null);
        $lawyer = Lawyer::query()->find($payload['lawyer_id'] ?? null);

        if (! $service || ! $lawyer) {
            return BookingResult::fail('INVALID_INPUT', 'Service and lawyer are required.');
        }

        if (! empty($payload['idempotency_key'])) {
            $existing = Appointment::query()->where('idempotency_key', $payload['idempotency_key'])->first();
            if ($existing) {
                return BookingResult::ok($existing);
            }
        }

        $firm = $this->firmService->current();
        $timezone = $this->availability->lawyerTimezone($lawyer, $firm);
        $startLocal = Carbon::parse($payload['start_at'], $timezone);
        $buffer = (int) ($service->buffer_minutes ?? $firm->buffer_minutes);
        $endLocal = $startLocal->copy()->addMinutes((int) $service->duration_minutes + $buffer);
        $lockKey = sprintf(
            'appointment:%d:%s:%s',
            $lawyer->id,
            $startLocal->toDateString(),
            $startLocal->format('H:i')
        );

        try {
            return DB::transaction(function () use ($payload, $service, $lawyer, $startLocal, $endLocal, $lockKey, $timezone) {
                $this->acquireLock($lockKey);

                if (! $this->availability->isSlotAvailable($service, $lawyer, $startLocal)) {
                    $alternatives = $this->availability->findSlots($service, $startLocal, $lawyer);

                    return BookingResult::conflict('Slot is no longer available.', array_slice($alternatives, 0, 5));
                }

                $lead = $this->leads->createOrUpdateFromCaller([
                    'name' => $payload['name'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'service_id' => $service->id,
                    'lawyer_id' => $lawyer->id,
                    'notes' => $payload['notes'] ?? null,
                    'source' => $payload['source'] ?? 'phone_ai',
                    'status' => LeadStatus::ConsultationBooked->value,
                ]);

                $appointment = Appointment::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'lead_id' => $lead->id,
                    'client_id' => $payload['client_id'] ?? null,
                    'service_id' => $service->id,
                    'start_at' => $startLocal->copy()->utc(),
                    'end_at' => $endLocal->copy()->utc(),
                    'status' => AppointmentStatus::Scheduled,
                    'source' => $payload['source'] ?? 'phone_ai',
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]);

                $this->audit->log('appointment.booked', $appointment, null, $appointment->toArray());
                $this->notifications->queueAppointmentBooked($appointment);

                return BookingResult::ok($appointment->load(['lawyer.user', 'service', 'lead']));
            });
        } catch (Throwable $e) {
            Log::error('Booking failed', ['error' => $e->getMessage(), 'lock' => $lockKey]);

            return BookingResult::fail('BOOKING_ERROR', 'Unable to complete booking.');
        } finally {
            $this->releaseLock($lockKey);
        }
    }

    public function reschedule(Appointment $appointment, string $newStartAt): BookingResult
    {
        $service = $appointment->service;
        $lawyer = $appointment->lawyer;
        $firm = $this->firmService->current();
        $timezone = $this->availability->lawyerTimezone($lawyer, $firm);
        $startLocal = Carbon::parse($newStartAt, $timezone);
        $buffer = (int) ($service->buffer_minutes ?? $firm->buffer_minutes);
        $endLocal = $startLocal->copy()->addMinutes((int) $service->duration_minutes + $buffer);
        $lockKey = sprintf('appointment:%d:%s:%s', $lawyer->id, $startLocal->toDateString(), $startLocal->format('H:i'));

        return DB::transaction(function () use ($appointment, $service, $lawyer, $startLocal, $endLocal, $lockKey) {
            $this->acquireLock($lockKey);

            if (! $this->availability->isSlotAvailable($service, $lawyer, $startLocal, $appointment->id)) {
                return BookingResult::conflict();
            }

            $old = $appointment->only(['start_at', 'end_at', 'status']);
            $appointment->update([
                'start_at' => $startLocal->copy()->utc(),
                'end_at' => $endLocal->copy()->utc(),
                'status' => AppointmentStatus::Scheduled,
            ]);
            $this->audit->log('appointment.rescheduled', $appointment, $old, $appointment->only(['start_at', 'end_at', 'status']));

            return BookingResult::ok($appointment->fresh());
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        $firm = $this->firmService->current();
        $hours = (int) $firm->cancellation_window_hours;
        if ($hours > 0 && $appointment->start_at->lt(now()->addHours($hours))) {
            // Still allow staff cancel; AI layer can enforce messaging separately.
        }

        $old = $appointment->only(['status', 'notes']);
        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'notes' => trim(($appointment->notes ? $appointment->notes."\n" : '').($reason ? 'Cancel reason: '.$reason : '')),
        ]);
        $this->audit->log('appointment.cancelled', $appointment, $old, $appointment->only(['status', 'notes']));
        $this->notifications->queueAppointmentCancelled($appointment);

        return $appointment->fresh();
    }

    private function acquireLock(string $key, int $timeoutSeconds = 5): void
    {
        // MySQL named lock — works without Redis for MVP concurrency safety.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $result = DB::selectOne('SELECT GET_LOCK(?, ?) as locked', [substr(md5($key), 0, 64), $timeoutSeconds]);
        if (! $result || (int) $result->locked !== 1) {
            throw new \RuntimeException('Could not acquire booking lock.');
        }
    }

    private function releaseLock(string $key): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::select('SELECT RELEASE_LOCK(?)', [substr(md5($key), 0, 64)]);
        } catch (Throwable) {
            // ignore
        }
    }
}

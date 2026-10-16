<?php

namespace App\Domain\AI;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\CRM\ClientService;
use App\Domain\CRM\LeadService;
use App\Domain\Firm\FirmService;
use App\Domain\Integrations\TwilioService;
use App\Domain\Scheduling\AvailabilityService;
use App\Jobs\SendAppointmentConfirmationJob;
use App\Models\AiSession;
use App\Models\AiToolCall;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\KnowledgeBaseItem;
use App\Models\Lawyer;
use App\Models\Scopes\FirmScope;
use App\Models\Service;
use Carbon\Carbon;

class AiToolRegistry
{
    public function __construct(
        private readonly FirmService $firmService,
        private readonly AvailabilityService $availability,
        private readonly AppointmentBookingService $booking,
        private readonly LeadService $leads,
        private readonly ClientService $clients,
        private readonly TwilioService $twilio,
    ) {}

    /**
     * @return list<string>
     */
    public function toolNames(): array
    {
        return [
            'get_firm_profile',
            'get_services',
            'get_service_details',
            'get_lawyers',
            'find_available_slots',
            'find_caller',
            'create_or_update_lead',
            'book_appointment',
            'reschedule_appointment',
            'cancel_appointment',
            'send_confirmation',
            'transfer_to_human',
            'create_callback_request',
            'get_knowledge_base',
        ];
    }

    public function invoke(string $tool, array $arguments = [], ?AiSession $session = null): array
    {
        $started = microtime(true);
        $status = 'success';
        try {
            $result = match ($tool) {
                'get_firm_profile' => $this->getFirmProfile(),
                'get_services' => $this->getServices($arguments),
                'get_service_details' => $this->getServiceDetails($arguments),
                'get_lawyers' => $this->getLawyers($arguments),
                'find_available_slots' => $this->findAvailableSlots($arguments),
                'find_caller' => $this->findCaller($arguments),
                'create_or_update_lead' => $this->createOrUpdateLead($arguments),
                'book_appointment' => $this->bookAppointment($arguments, $session),
                'reschedule_appointment' => $this->rescheduleAppointment($arguments),
                'cancel_appointment' => $this->cancelAppointment($arguments),
                'send_confirmation' => $this->sendConfirmation($arguments),
                'transfer_to_human' => $this->transferToHuman($arguments, $session),
                'create_callback_request' => $this->createCallbackRequest($arguments, $session),
                'get_knowledge_base' => $this->getKnowledgeBase(),
                default => ['error' => 'UNKNOWN_TOOL', 'message' => 'Tool is not registered.'],
            };
            if (isset($result['error'])) {
                $status = 'error';
            }
        } catch (\Throwable $e) {
            $status = 'error';
            $result = ['error' => 'TOOL_EXCEPTION', 'message' => 'Tool execution failed.'];
        }

        $durationMs = (int) ((microtime(true) - $started) * 1000);
        if ($session) {
            AiToolCall::query()->create([
                'ai_session_id' => $session->id,
                'tool_name' => $tool,
                'arguments_json' => $arguments,
                'result_json' => $result,
                'status' => $status,
                'duration_ms' => $durationMs,
            ]);
        }

        return $result;
    }

    private function getFirmProfile(): array
    {
        $firm = $this->firmService->current();

        return [
            'firm_name' => $firm->firm_name,
            'legal_name' => $firm->legal_name,
            'phone' => $firm->phone,
            'email' => $firm->email,
            'address' => $firm->address,
            'website' => $firm->website,
            'timezone' => $firm->timezone,
            'receptionist_name' => $firm->receptionist_name,
            'currency' => $firm->currency,
        ];
    }

    private function getServices(array $args): array
    {
        $query = Service::query()->aiVisible()->with('category');
        if (! empty($args['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', '%'.$args['category'].'%'));
        }

        return $query->with('lawyers.user')->get()->map(function (Service $s) {
            $lawyers = $s->lawyers->map(fn (Lawyer $l) => [
                'id' => $l->id,
                'name' => $l->displayName(),
                'title' => $l->title,
            ])->values()->all();
            $feeLabel = trim(($s->currency ?: '').' '.number_format((float) $s->price, 2));
            $lawyerNames = $lawyers === []
                ? 'to be assigned'
                : implode(', ', array_map(fn (array $l) => $l['name'], $lawyers));

            return [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category?->name,
                'short_description' => str($s->description)->limit(160)->toString(),
                'price' => (string) $s->price,
                'currency' => $s->currency,
                'fee_label' => $feeLabel,
                'duration_minutes' => $s->duration_minutes,
                'lawyers' => $lawyers,
                'speak' => sprintf(
                    '%s (handled by %s).',
                    $s->name,
                    $lawyerNames,
                ),
            ];
        })->all();
    }

    private function getServiceDetails(array $args): array
    {
        // Synthflow may pass service_id as a string variable — cast to int
        $serviceId = isset($args['service_id']) ? (int) $args['service_id'] : null;
        $service = Service::query()->aiVisible()->with('lawyers.user')->find($serviceId);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not available. Please call get_services first to get the correct service_id.'];
        }

        $lawyers = $service->lawyers->map(fn (Lawyer $l) => [
            'id' => $l->id,
            'name' => $l->displayName(),
            'title' => $l->title,
            'specialties' => $l->specialties ?? [],
        ])->values()->all();

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => (string) $service->price,
            'currency' => $service->currency,
            'fee_label' => trim(($service->currency ?: '').' '.number_format((float) $service->price, 2)),
            'duration_minutes' => $service->duration_minutes,
            'booking_enabled' => $service->booking_enabled,
            'lawyers' => $lawyers,
            'speak' => sprintf(
                '%s costs %s for about %d minutes. Lawyer(s): %s.',
                $service->name,
                trim(($service->currency ?: '').' '.number_format((float) $service->price, 2)),
                (int) $service->duration_minutes,
                $lawyers === []
                    ? 'to be assigned'
                    : implode(', ', array_map(fn (array $l) => $l['name'], $lawyers)),
            ),
        ];
    }

    private function getLawyers(array $args): array
    {
        $query = Lawyer::query()->active()->with('user');
        if (! empty($args['service_id'])) {
            // Cast to int — Synthflow may send as string
            $serviceId = (int) $args['service_id'];
            $query->whereHas('services', fn ($q) => $q->where('services.id', $serviceId));
        }

        return $query->get()->map(fn (Lawyer $l) => [
            'id' => $l->id,
            'name' => $l->displayName(),
            'title' => $l->title,
            'specialties' => $l->specialties ?? [],
        ])->all();
    }

    private function findAvailableSlots(array $args): array
    {
        // Cast IDs to int — Synthflow variable substitution may pass them as strings
        $serviceId = isset($args['service_id']) ? (int) $args['service_id'] : null;
        $lawyerId  = isset($args['lawyer_id']) && $args['lawyer_id'] !== '' ? (int) $args['lawyer_id'] : null;

        if (! $serviceId) {
            return [
                'error'   => 'MISSING_SERVICE_ID',
                'message' => 'service_id is required. Call get_services first to obtain the numeric service_id, then pass it here.',
            ];
        }

        $service = Service::query()->bookable()->find($serviceId);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not bookable. Make sure the service is active and booking is enabled.'];
        }
        $lawyer = $lawyerId ? Lawyer::query()->find($lawyerId) : null;
        $date = Carbon::parse($args['date'] ?? now()->toDateString());

        $slots = $this->availability->findSlots($service, $date, $lawyer);

        if (empty($slots)) {
            // Return a helpful message instead of empty array so agent can respond properly
            return [
                'slots'   => [],
                'message' => 'No slots available on ' . $date->toDateString() . '. Try a different date.',
                'date'    => $date->toDateString(),
            ];
        }

        return [
            'slots'   => $slots,
            'date'    => $date->toDateString(),
            'count'   => count($slots),
        ];
    }

    private function findCaller(array $args): array
    {
        $phone = $args['phone'] ?? null;
        $lead = $this->leads->findByPhone($phone);
        $client = $this->clients->findByPhone($phone);

        return [
            'lead' => $lead?->only(['id', 'name', 'phone', 'email', 'status']),
            'client' => $client?->only(['id', 'name', 'phone', 'email', 'status']),
            'found' => (bool) ($lead || $client),
        ];
    }

    private function createOrUpdateLead(array $args): array
    {
        $lead = $this->leads->createOrUpdateFromCaller($args);

        return [
            'lead_id' => $lead->id,
            'status' => $lead->status?->value ?? $lead->status,
            'created_or_updated' => 'updated',
        ];
    }

    private function bookAppointment(array $args, ?AiSession $session = null): array
    {
        // Always attach the live call so the appointment shows as call-booked.
        if ($session?->call_id && empty($args['call_id'])) {
            $args['call_id'] = $session->call_id;
        }

        if (empty($args['call_id'])) {
            $providerCallId = (string) ($args['provider_call_id'] ?? $args['call_sid'] ?? '');
            if ($providerCallId !== '') {
                $callId = Call::query()
                    ->withoutGlobalScope(FirmScope::class)
                    ->where('provider_call_id', $providerCallId)
                    ->value('id');
                if ($callId) {
                    $args['call_id'] = (int) $callId;
                }
            }
        }

        // Voice agent bookings are always phone_ai (never staff).
        $args['source'] = 'phone_ai';

        // Normalize fields from Synthflow:
        // Synthflow may send phone as array/object: e.g. ["value" => "+92300..."]
        if (isset($args['phone'])) {
            if (is_array($args['phone'])) {
                $args['phone'] = (string) (
                    $args['phone']['value']
                    ?? $args['phone']['number']
                    ?? $args['phone']['phone']
                    ?? reset($args['phone'])
                    ?? ''
                );
            } else {
                $args['phone'] = (string) $args['phone'];
            }
        }

        if (isset($args['name']) && is_array($args['name'])) {
            $args['name'] = (string) ($args['name']['value'] ?? reset($args['name']) ?? '');
        }

        if (isset($args['email']) && is_array($args['email'])) {
            $args['email'] = (string) ($args['email']['value'] ?? reset($args['email']) ?? '');
        }

        if (isset($args['notes']) && is_array($args['notes'])) {
            $args['notes'] = (string) ($args['notes']['value'] ?? reset($args['notes']) ?? '');
        }

        if (isset($args['service_id'])) {
            $args['service_id'] = (int) (is_array($args['service_id']) ? reset($args['service_id']) : $args['service_id']);
        }

        if (isset($args['lawyer_id'])) {
            $args['lawyer_id'] = (int) (is_array($args['lawyer_id']) ? reset($args['lawyer_id']) : $args['lawyer_id']);
        }

        if (empty($args['service_id']) || empty($args['lawyer_id']) || empty($args['start_at'])) {
            return [
                'error' => 'INVALID_INPUT',
                'message' => 'service_id, lawyer_id, and start_at are required to book on the call.',
            ];
        }

        $result = $this->booking->book($args)->toArray();

        if (($result['success'] ?? false) && ! empty($args['call_id'])) {
            Call::query()
                ->withoutGlobalScope(FirmScope::class)
                ->whereKey((int) $args['call_id'])
                ->update([
                    'outcome' => 'appointment_booked',
                    'lead_id' => $result['appointment']['lead_id'] ?? null,
                    'lawyer_id' => $result['appointment']['lawyer_id'] ?? null,
                ]);
        }

        return $result;
    }

    private function rescheduleAppointment(array $args): array
    {
        $appointment = Appointment::query()->find($args['appointment_id'] ?? null);
        if (! $appointment) {
            return ['error' => 'NOT_FOUND', 'message' => 'Appointment not found.'];
        }

        return $this->booking->reschedule($appointment, $args['new_start_at'])->toArray();
    }

    private function cancelAppointment(array $args): array
    {
        $appointment = Appointment::query()->find($args['appointment_id'] ?? null);
        if (! $appointment) {
            return ['error' => 'NOT_FOUND', 'message' => 'Appointment not found.'];
        }
        $cancelled = $this->booking->cancel($appointment, $args['reason'] ?? null);

        return [
            'success' => true,
            'appointment_id' => $cancelled->id,
            'status' => $cancelled->status?->value,
        ];
    }

    private function sendConfirmation(array $args): array
    {
        $appointment = Appointment::query()->find($args['appointment_id'] ?? null);
        if (! $appointment) {
            return ['error' => 'NOT_FOUND', 'message' => 'Appointment not found.'];
        }

        SendAppointmentConfirmationJob::dispatch($appointment->id);

        return [
            'status' => 'queued',
            'appointment_id' => $appointment->id,
            'channel' => $args['channel'] ?? 'both',
        ];
    }

    private function transferToHuman(array $args, ?AiSession $session): array
    {
        $firm = $this->firmService->current();
        $call = $session?->call;
        if ($call) {
            $call->update([
                'outcome' => 'transferred',
                'summary' => trim(($call->summary ? $call->summary."\n" : '').'Transfer reason: '.($args['reason'] ?? 'caller_request')),
            ]);
        }

        $transferred = false;
        if ($firm->transfer_number && $call?->provider_call_id) {
            $transferred = $this->twilio->transferCall($call->provider_call_id, $firm->transfer_number);
        }

        return [
            'success' => $transferred || (bool) $firm->transfer_number,
            'transferred' => $transferred,
            'transfer_number' => $firm->transfer_number,
            'reason' => $args['reason'] ?? null,
        ];
    }

    private function createCallbackRequest(array $args, ?AiSession $session): array
    {
        $lead = $this->leads->createOrUpdateFromCaller([
            'name' => $args['name'] ?? null,
            'phone' => $args['phone'] ?? null,
            'email' => $args['email'] ?? null,
            'notes' => $args['reason'] ?? null,
            'source' => 'phone_ai',
        ]);

        $callback = CallbackRequest::query()->create([
            'name' => $args['name'] ?? $lead->name,
            'phone' => $args['phone'] ?? $lead->phone,
            'email' => $args['email'] ?? $lead->email,
            'reason' => $args['reason'] ?? null,
            'lead_id' => $lead->id,
            'call_id' => $session?->call_id,
            'status' => 'open',
        ]);

        return ['callback_id' => $callback->id, 'lead_id' => $lead->id];
    }

    private function getKnowledgeBase(): array
    {
        return KnowledgeBaseItem::query()->aiVisible()->get(['id', 'type', 'title', 'content'])->toArray();
    }
}

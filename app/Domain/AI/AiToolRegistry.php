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
use App\Models\CallbackRequest;
use App\Models\KnowledgeBaseItem;
use App\Models\Lawyer;
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
                'book_appointment' => $this->bookAppointment($arguments),
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

        return $query->get()->map(fn (Service $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'category' => $s->category?->name,
            'short_description' => str($s->description)->limit(160)->toString(),
            'price' => (string) $s->price,
            'currency' => $s->currency,
            'duration_minutes' => $s->duration_minutes,
        ])->all();
    }

    private function getServiceDetails(array $args): array
    {
        $service = Service::query()->aiVisible()->find($args['service_id'] ?? null);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not available.'];
        }

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => (string) $service->price,
            'currency' => $service->currency,
            'duration_minutes' => $service->duration_minutes,
            'booking_enabled' => $service->booking_enabled,
        ];
    }

    private function getLawyers(array $args): array
    {
        $query = Lawyer::query()->active()->with('user');
        if (! empty($args['service_id'])) {
            $query->whereHas('services', fn ($q) => $q->where('services.id', $args['service_id']));
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
        $service = Service::query()->bookable()->find($args['service_id'] ?? null);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not bookable.'];
        }
        $lawyer = ! empty($args['lawyer_id']) ? Lawyer::query()->find($args['lawyer_id']) : null;
        $date = Carbon::parse($args['date'] ?? now()->toDateString());

        return [
            'slots' => $this->availability->findSlots($service, $date, $lawyer),
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

    private function bookAppointment(array $args): array
    {
        return $this->booking->book($args)->toArray();
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

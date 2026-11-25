<?php

namespace App\Domain\AI;

use App\Domain\Appointments\AppointmentBookingService;
use App\Domain\CRM\ClientService;
use App\Domain\CRM\LeadService;
use App\Domain\Firm\FirmService;
use App\Domain\Integrations\TwilioService;
use App\Domain\Scheduling\AvailabilityService;
use App\Domain\Scheduling\FirmTime;
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
use App\Support\SpokenFormat;
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
            'list_available_days',
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
                'list_available_days' => $this->listAvailableDays($arguments),
                'find_available_slots' => $this->findAvailableSlots($arguments),
                'find_caller' => $this->findCaller($arguments),
                'create_or_update_lead' => $this->createOrUpdateLead($arguments, $session),
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
        $firm = $this->firmService->current();
        $query = Service::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->where('ai_enabled', true)
            ->with('category');
        if (! empty($args['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', '%'.$args['category'].'%'));
        }

        $services = $query->with([
            'lawyers' => fn ($q) => $q->withoutGlobalScopes()
                ->where('lawyers.firm_id', $firm->id)
                ->where('lawyers.status', 'active')
                ->with('user'),
        ])->get();

        $hasAnyActiveLawyers = Lawyer::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->exists();

        $rows = $services->values()->map(function (Service $s) use ($firm) {
            $lawyers = $s->lawyers->map(fn (Lawyer $l) => [
                'id' => $l->id,
                'name' => $l->displayName(),
                'title' => $l->title,
            ])->values()->all();
            $currency = (string) ($s->currency ?: $firm->currency ?: 'USD');
            $feeLabel = SpokenFormat::money($s->price, $currency);
            $lawyerNames = $lawyers === []
                ? 'an available lawyer'
                : implode(', ', array_map(fn (array $l) => $l['name'], $lawyers));

            return [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category?->name,
                'short_description' => str($s->description)->limit(160)->toString(),
                'price' => (string) $s->price,
                'currency' => $currency,
                'fee_label' => $feeLabel,
                'duration_minutes' => $s->duration_minutes,
                'lawyers' => $lawyers,
                'speak' => sprintf('%s with %s', $s->name, $lawyerNames),
            ];
        })->all();

        $menu = ($rows === [] || ! $hasAnyActiveLawyers)
            ? "I'm sorry, I can't take bookings right now, but I can have the team call you back. Could you share your name, please?"
            : ('We offer '
                .$this->naturalJoin(array_map(fn (array $row) => $row['speak'], $rows))
                .'. Which service would you like?');

        return [
            'menu' => $menu,
            'speak' => $menu,
            'count' => count($rows),
            'services' => $rows,
        ];
    }

    private function getServiceDetails(array $args): array
    {
        $firm = $this->firmService->current();
        $serviceId = isset($args['service_id']) ? (int) $args['service_id'] : null;
        $service = Service::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->where('ai_enabled', true)
            ->with([
                'lawyers' => fn ($q) => $q->withoutGlobalScopes()
                    ->where('lawyers.firm_id', $firm->id)
                    ->where('lawyers.status', 'active')
                    ->with('user'),
            ])
            ->find($serviceId);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not available. Please call get_services first to get the correct service_id.'];
        }

        $lawyers = $service->lawyers->map(fn (Lawyer $l) => [
            'id' => $l->id,
            'name' => $l->displayName(),
            'title' => $l->title,
            'specialties' => $l->specialties ?? [],
        ])->values()->all();

        $currency = (string) ($service->currency ?: $firm->currency ?: 'USD');
        $spokenFee = SpokenFormat::money($service->price, $currency);
        $duration = (int) ($service->duration_minutes ?? 0);
        $lawyerNames = $lawyers === []
            ? 'an available lawyer'
            : $this->naturalJoin(array_map(fn (array $l) => $l['name'], $lawyers));

        $durationClause = $duration > 0 ? sprintf(' for %d minutes', $duration) : '';
        $speak = sprintf(
            '%s is %s%s with %s. Would you like to book a consultation for %s?',
            $service->name,
            $spokenFee,
            $durationClause,
            $lawyerNames,
            $service->name,
        );

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => (string) $service->price,
            'currency' => $currency,
            'fee_label' => $spokenFee,
            'duration_minutes' => $duration > 0 ? $duration : null,
            'booking_enabled' => $service->booking_enabled,
            'lawyers' => $lawyers,
            'speak' => $speak,
            'next_step' => 'Ask if they would like to book this consultation and wait for confirmation.',
        ];
    }

    private function getLawyers(array $args): array
    {
        $firm = $this->firmService->current();
        $query = Lawyer::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('status', 'active')
            ->with('user');
        if (! empty($args['service_id'])) {
            $serviceId = (int) $args['service_id'];
            $query->whereHas('services', fn ($q) => $q->withoutGlobalScopes()
                ->where('services.firm_id', $firm->id)
                ->where('services.id', $serviceId));
        }

        return $query->get()->map(function (Lawyer $l) {
            $hours = $this->availability->weeklyHoursSummary($l);
            $speakHours = $hours === []
                ? 'no weekly hours configured'
                : implode('; ', array_map(
                    fn (array $h) => $h['weekday'].' '.$h['start_label'].' to '.$h['end_label'],
                    $hours,
                ));

            return [
                'id' => $l->id,
                'name' => $l->displayName(),
                'title' => $l->title,
                'specialties' => $l->specialties ?? [],
                'weekly_hours' => $hours,
                'speak' => $l->displayName().' is usually available '.$speakHours.'.',
            ];
        })->all();
    }

    private function listAvailableDays(array $args): array
    {
        $serviceId = isset($args['service_id']) ? (int) $args['service_id'] : null;
        $lawyerId = isset($args['lawyer_id']) && $args['lawyer_id'] !== '' ? (int) $args['lawyer_id'] : null;

        if (! $serviceId || ! $lawyerId) {
            return [
                'error' => 'MISSING_IDS',
                'message' => 'service_id and lawyer_id are required. Call get_services / get_lawyers first.',
            ];
        }

        $service = Service::query()->bookable()->find($serviceId);
        if (! $service) {
            return ['error' => 'NOT_FOUND', 'message' => 'Service not bookable.'];
        }

        $lawyer = Lawyer::query()->active()->find($lawyerId);
        if (! $lawyer) {
            return ['error' => 'NOT_FOUND', 'message' => 'Lawyer not found.'];
        }

        $days = $this->availability->listAvailableDaysInWeek($service, $lawyer);

        if ($days === []) {
            return [
                'days' => [],
                'lawyer_id' => $lawyer->id,
                'lawyer_name' => $lawyer->displayName(),
                'slot_minutes' => AvailabilityService::slotMinutesFor(),
                'message' => $lawyer->displayName().' has no free slots left this week. Offer next week or another lawyer.',
            ];
        }

        $dayNames = array_map(fn (array $d) => $d['weekday'], $days);
        $speak = $lawyer->displayName().' is available this week on '
            .$this->naturalJoin($dayNames)
            .'. Which day would you like?';

        return [
            'days' => array_values($days),
            'lawyer_id' => $lawyer->id,
            'lawyer_name' => $lawyer->displayName(),
            'slot_minutes' => AvailabilityService::slotMinutesFor(),
            'count' => count($days),
            'menu' => $speak,
            'speak' => $speak,
        ];
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
                'windows' => [],
                'message' => 'No availability on '.$date->toDateString().'. Call list_available_days and offer another day.',
                'date'    => $date->toDateString(),
                'slot_minutes' => AvailabilityService::slotMinutesFor(),
                'speak' => 'That day is fully booked. Which other day works for you?',
            ];
        }

        $windows = $this->availabilityWindowsFromSlots($slots);
        $windowSpeak = $this->naturalJoin(array_map(
            fn (array $w) => $w['start_label'].' to '.$w['end_label'],
            $windows,
        ));

        $serviceDuration = (int) ($service->duration_minutes ?? 0);
        $durationClause = $serviceDuration > 0
            ? ' Consultations are '.$serviceDuration.' minutes.'
            : '';

        $speak = 'On '.$date->format('l, M j').', '
            .($lawyer?->displayName() ?: 'the lawyer')
            .' is available from '.$windowSpeak
            .'.'.$durationClause.' What time would you like?';

        return [
            // Keep slots for booking match; do not read them aloud.
            'slots' => $slots,
            'windows' => $windows,
            'available_from' => $windows[0]['start_label'] ?? null,
            'available_until' => $windows[array_key_last($windows)]['end_label'] ?? null,
            'all_slot_count' => count($slots),
            'date' => $date->toDateString(),
            'count' => count($slots),
            'duration_minutes' => $serviceDuration > 0 ? $serviceDuration : null,
            'menu' => $speak,
            'speak' => $speak,
            'next_step' => 'Ask the caller to choose any time within the availability window. Map their time to the nearest matching slots[].start_at, then book. Never list individual slot options aloud.',
        ];
    }

    /**
     * @param  list<string>  $parts
     */
    private function naturalJoin(array $parts): string
    {
        $parts = array_values(array_filter($parts, fn ($p) => is_string($p) && $p !== ''));
        $count = count($parts);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $parts[0];
        }
        if ($count === 2) {
            return $parts[0].' and '.$parts[1];
        }

        return implode(', ', array_slice($parts, 0, -1)).', and '.$parts[$count - 1];
    }

    /**
     * Merge free slots into spoken availability windows (start → end), not every 30-min option.
     *
     * @param  list<array<string, mixed>>  $slots
     * @return list<array{start_at: string, end_at: string, start_label: string, end_label: string}>
     */
    private function availabilityWindowsFromSlots(array $slots): array
    {
        if ($slots === []) {
            return [];
        }

        $tz = (string) ($slots[0]['timezone'] ?? $this->firmService->current()->resolvedTimezone());
        $windows = [];
        $windowStart = $slots[0];
        $prev = $slots[0];

        $slotMinutes = AvailabilityService::slotMinutesFor();

        for ($i = 1, $n = count($slots); $i < $n; $i++) {
            $slot = $slots[$i];
            $prevStartLocal = Carbon::parse((string) ($prev['start_local'] ?? $prev['start_at']), $tz);
            $slotStartLocal = Carbon::parse((string) ($slot['start_local'] ?? $slot['start_at']), $tz);

            // Contiguous firm-slot starts stay in one window; a larger gap opens a new window.
            if ($slotStartLocal->gt($prevStartLocal->copy()->addMinutes($slotMinutes))) {
                $endLocal = $prevStartLocal->copy()->addMinutes($slotMinutes);
                $windows[] = [
                    'start_at' => (string) ($windowStart['start_at'] ?? ''),
                    'end_at' => (string) ($prev['end_at'] ?? $endLocal->copy()->utc()->toIso8601String()),
                    'start_label' => (string) ($windowStart['label'] ?? $windowStart['start_local'] ?? ''),
                    'end_label' => $endLocal->format('g:i A'),
                ];
                $windowStart = $slot;
            }
            $prev = $slot;
        }

        $prevStartLocal = Carbon::parse((string) ($prev['start_local'] ?? $prev['start_at']), $tz);
        $endLocal = $prevStartLocal->copy()->addMinutes($slotMinutes);
        $windows[] = [
            'start_at' => (string) ($windowStart['start_at'] ?? ''),
            'end_at' => (string) ($prev['end_at'] ?? $endLocal->copy()->utc()->toIso8601String()),
            'start_label' => (string) ($windowStart['label'] ?? ''),
            'end_label' => $endLocal->format('g:i A'),
        ];

        return $windows;
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

    private function createOrUpdateLead(array $args, ?AiSession $session = null): array
    {
        $resolvedPhone = $this->resolveCallerPhone($args, $session);
        if ($resolvedPhone !== '') {
            $args['phone'] = $resolvedPhone;
        }
        $lead = $this->leads->createOrUpdateFromCaller($args);

        return [
            'lead_id' => $lead->id,
            'status' => $lead->status?->value ?? $lead->status,
            'created_or_updated' => 'updated',
        ];
    }

    private function bookAppointment(array $args, ?AiSession $session = null): array
    {
        $firm = $this->firmService->current();

        // Always attach the live call so the appointment shows as call-booked.
        if ($session?->call_id && empty($args['call_id'])) {
            $args['call_id'] = $session->call_id;
        }

        if (empty($args['call_id'])) {
            $providerCallId = (string) ($args['provider_call_id'] ?? $args['call_sid'] ?? '');
            if ($providerCallId !== '') {
                $callId = Call::query()
                    ->withoutGlobalScope(FirmScope::class)
                    ->where('firm_id', $firm->id)
                    ->where('provider_call_id', $providerCallId)
                    ->value('id');
                if ($callId) {
                    $args['call_id'] = (int) $callId;
                }
            }
        }

        // Voice agent bookings are always phone_ai (never staff).
        $args['source'] = 'phone_ai';

        // Stable idempotency so FineTuner retries / second tool calls cannot create extra rows.
        if (empty($args['idempotency_key'])) {
            if (! empty($args['call_id'])) {
                $args['idempotency_key'] = 'phone_ai:call:'.(int) $args['call_id'];
            } elseif (! empty($args['provider_call_id'])) {
                $args['idempotency_key'] = 'phone_ai:provider:'.trim((string) $args['provider_call_id']);
            }
        }

        // Normalize fields from Synthflow (including customer_phone and "same").
        $args['phone'] = $this->resolveCallerPhone($args, $session);

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

        $booking = $this->booking->book($args);
        $result = $booking->toArray();

        if (($result['success'] ?? false) && ! empty($args['call_id'])) {
            Call::query()
                ->withoutGlobalScope(FirmScope::class)
                ->where('firm_id', $firm->id)
                ->whereKey((int) $args['call_id'])
                ->update([
                    'outcome' => 'appointment_booked',
                    'lead_id' => $result['appointment']['lead_id'] ?? null,
                    'lawyer_id' => $result['appointment']['lawyer_id'] ?? null,
                ]);
        }

        if (($result['success'] ?? false) && $booking->appointment) {
            $appt = $booking->appointment;
            $localStart = app(FirmTime::class)->toFirm($appt->start_at, $firm);
            $serviceName = $appt->service?->name ?: 'your consultation';
            $lawyerName = $appt->lawyer?->displayName() ?: 'our lawyer';
            $result['speak'] = sprintf(
                'Your consultation for %s with %s is booked and confirmed for %s at %s.',
                $serviceName,
                $lawyerName,
                $localStart->format('l, F j'),
                $localStart->format('g:i A'),
            );
        }

        return $result;
    }

    private function resolveCallerPhone(array $args, ?AiSession $session = null): string
    {
        $raw = $args['phone'] ?? $args['customer_phone'] ?? '';
        if (is_array($raw)) {
            $raw = (string) ($raw['value'] ?? $raw['number'] ?? $raw['phone'] ?? reset($raw) ?? '');
        }
        $phone = trim((string) $raw);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $phone) ?: '');

        $samePhrases = [
            'same',
            'same number',
            'this number',
            'the one i am calling from',
            "the one i'm calling from",
            'caller id',
        ];

        if ($phone !== '' && ! in_array($normalized, $samePhrases, true)) {
            return $phone;
        }

        if (! empty($session?->call?->caller_phone)) {
            return (string) $session->call->caller_phone;
        }

        $firm = $this->firmService->current();
        if (! empty($args['call_id'])) {
            $found = Call::query()
                ->withoutGlobalScope(FirmScope::class)
                ->where('firm_id', $firm->id)
                ->whereKey((int) $args['call_id'])
                ->value('caller_phone');
            if ($found) {
                return (string) $found;
            }
        }

        if (! empty($args['provider_call_id'])) {
            $found = Call::query()
                ->withoutGlobalScope(FirmScope::class)
                ->where('firm_id', $firm->id)
                ->where('provider_call_id', (string) $args['provider_call_id'])
                ->value('caller_phone');
            if ($found) {
                return (string) $found;
            }
        }

        return $phone;
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
        $resolvedPhone = $this->resolveCallerPhone($args, $session);
        if ($resolvedPhone !== '') {
            $args['phone'] = $resolvedPhone;
        }
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

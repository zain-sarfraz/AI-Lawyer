<?php

declare(strict_types=1);

namespace App\Domain\Integrations;

use App\Domain\AI\AiReceptionistService;
use App\Domain\Firm\FirmService;
use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FineTunerService
{
    private const READ_TIMEOUT_SECONDS = 4;

    private const SNAPSHOT_TTL_SECONDS = 300;

    private const SNAPSHOT_ERROR_TTL_SECONDS = 60;

    /** @var array{api_key: string, workspace_id: string, base_url: string}|null|false */
    private array|null|false $connectionCache = false;

    /** @var array<string, mixed>|null */
    private ?array $settingsCache = null;

    /** @var \App\Models\Integration|null|false */
    private mixed $integrationCache = false;

    public function __construct(
        private readonly IntegrationCredentialService $credentials,
        private readonly AiReceptionistService $receptionist,
        private readonly FirmService $firmService,
    ) {}

    public function configured(): bool
    {
        return $this->connection() !== null;
    }

    /**
     * Lightweight local status for pages that must not block on Synthflow HTTP.
     *
     * @return array{connected: bool, error: ?string, agents: list<array<string, mixed>>, numbers: list<array<string, mixed>>, model_id: ?string, phone_number: ?string, last_synced_at: ?string}
     */
    public function localStatus(): array
    {
        $connection = $this->connection();
        $settings = $this->settings();
        $modelId = $settings['model_id'] ?? config('services.synthflow.agent_id');
        $phone = $settings['phone_number'] ?? config('services.synthflow.agent_phone');
        $agentName = (string) ($settings['agent_name'] ?? config('services.synthflow.agent_name', 'Qubetech AI Lawyer'));

        $agents = [];
        if (is_string($modelId) && $modelId !== '') {
            $agents[] = [
                'id' => $modelId,
                'name' => $agentName,
                'description' => '',
                'phone_number' => is_string($phone) ? $phone : '',
                'type' => 'inbound',
                'voice' => '',
            ];
        }

        $numbers = [];
        if (is_string($phone) && $phone !== '') {
            $numbers[] = ['number' => $phone, 'available' => true];
        }

        return [
            'connected' => $connection !== null,
            'error' => null,
            'agents' => $agents,
            'numbers' => $numbers,
            'model_id' => is_string($modelId) ? $modelId : null,
            'phone_number' => is_string($phone) ? $phone : null,
            'last_synced_at' => $settings['last_synced_at'] ?? null,
        ];
    }

    /**
     * @return array{api_key: string, workspace_id: string, base_url: string}|null
     */
    public function connection(): ?array
    {
        if ($this->connectionCache !== false) {
            return $this->connectionCache;
        }

        $integration = $this->integration();
        $stored = $integration?->status === 'active' ? ($integration->encrypted_credentials ?? []) : [];
        $settings = $this->settings();

        $apiKey = $stored['api_key'] ?? config('services.synthflow.api_key') ?? config('services.finetuner.api_key');
        $workspaceId = $settings['workspace_id'] ?? config('services.synthflow.workspace_id') ?? config('services.finetuner.workspace_id');
        $baseUrl = rtrim((string) ($settings['base_url'] ?? config('services.synthflow.base_url') ?? config('services.finetuner.base_url')), '/');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($workspaceId) || $workspaceId === '') {
            return $this->connectionCache = null;
        }

        return $this->connectionCache = [
            'api_key' => $apiKey,
            'workspace_id' => $workspaceId,
            'base_url' => $baseUrl !== '' ? $baseUrl : (string) config('services.synthflow.base_url', 'https://api.us.synthflow.ai/v2'),
        ];
    }

    public function maskedKey(): ?string
    {
        return $this->integration()?->maskedCredentials()['api_key'] ?? null;
    }

    /**
     * @return array{connected: bool, error: ?string, agents: list<array<string, mixed>>, numbers: list<array<string, mixed>>, model_id: ?string, phone_number: ?string, last_synced_at: ?string}
     */
    public function snapshot(): array
    {
        $connection = $this->connection();
        $settings = $this->settings();
        if ($connection === null) {
            return [
                'connected' => false,
                'error' => null,
                'agents' => [],
                'numbers' => [],
                'model_id' => $settings['model_id'] ?? null,
                'phone_number' => $settings['phone_number'] ?? null,
                'last_synced_at' => $settings['last_synced_at'] ?? null,
            ];
        }

        $cacheKey = $this->snapshotCacheKey($connection);
        $remote = Cache::get($cacheKey);
        if (! is_array($remote)) {
            $agents = $this->listAgents($connection);
            $numbers = $this->listNumbers($connection);
            $error = $agents['error'] ?? $numbers['error'] ?? null;

            $remote = [
                'connected' => $error === null,
                'error' => $error,
                'agents' => $agents['items'],
                'numbers' => $numbers['items'],
            ];

            Cache::put(
                $cacheKey,
                $remote,
                $error === null ? self::SNAPSHOT_TTL_SECONDS : self::SNAPSHOT_ERROR_TTL_SECONDS
            );
        }

        return $remote + [
            'model_id' => $settings['model_id'] ?? null,
            'phone_number' => $settings['phone_number'] ?? null,
            'last_synced_at' => $settings['last_synced_at'] ?? null,
        ];
    }

    /**
     * Validate Synthflow credentials, then store them.
     *
     * @return array{ok: bool, error?: string}
     */
    public function connect(string $apiKey, string $workspaceId, ?string $baseUrl = null): array
    {
        $baseUrl = rtrim($baseUrl ?: (string) config('services.synthflow.base_url', 'https://api.us.synthflow.ai/v2'), '/');

        $probe = $this->probeConnection($apiKey, $workspaceId, $baseUrl);
        if (! $probe['ok']) {
            return $probe;
        }

        $existing = $this->settings();
        $this->credentials->upsert('finetuner', [
            'api_key' => $apiKey,
        ], array_merge($existing, [
            'workspace_id' => $workspaceId,
            'base_url' => $baseUrl,
            'provider_label' => 'synthflow',
        ]));

        $this->forgetSnapshotCache($workspaceId, $apiKey);

        $linked = $this->adoptExistingAgent();

        return [
            'ok' => true,
            'model_id' => $linked['model_id'] ?? null,
            'name' => $linked['name'] ?? null,
            'phone_number' => $linked['phone_number'] ?? null,
            'linked' => (bool) ($linked['ok'] ?? false),
            'error' => ($linked['ok'] ?? false) ? ($linked['error'] ?? null) : ($linked['error'] ?? null),
        ];
    }

    /**
     * Attach the existing inbound Synthflow agent (by saved id, phone, or name) instead of creating one.
     *
     * @return array{ok: bool, model_id?: string, name?: string, phone_number?: ?string, error?: string}
     */
    public function adoptExistingAgent(): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Connect Synthflow first (API key + workspace).'];
        }

        $this->forgetSnapshotCache($connection['workspace_id'], $connection['api_key']);
        $listed = $this->listAgents($connection);
        if ($listed['error'] !== null) {
            return ['ok' => false, 'error' => $listed['error']];
        }

        $preferredPhone = $this->preferredPhone();
        $preferredName = strtolower(trim((string) config('services.synthflow.agent_name', 'Qubetech AI Lawyer')));
        $configuredId = trim((string) config('services.synthflow.agent_id', ''));
        $agents = $listed['items'];
        $currentId = $this->settings()['model_id'] ?? null;

        $match = null;
        if ($configuredId !== '') {
            $match = collect($agents)->first(fn (array $agent) => $agent['id'] === $configuredId);
        }
        if ($match === null && is_string($currentId) && $currentId !== '') {
            $match = collect($agents)->first(fn (array $agent) => $agent['id'] === $currentId);
        }
        if ($match === null && $preferredPhone !== null) {
            $match = collect($agents)->first(fn (array $agent) => $this->phonesMatch((string) ($agent['phone_number'] ?? ''), $preferredPhone));
        }
        if ($match === null && $preferredName !== '') {
            $match = collect($agents)->first(fn (array $agent) => str_contains(strtolower((string) $agent['name']), $preferredName));
        }
        if ($match === null && $configuredId !== '') {
            $match = [
                'id' => $configuredId,
                'name' => (string) config('services.synthflow.agent_name', 'Qubetech AI Lawyer'),
                'phone_number' => $preferredPhone,
            ];
        }
        if ($match === null) {
            $inbound = collect($agents)->filter(fn (array $agent) => ($agent['type'] ?? 'inbound') === 'inbound')->values();
            if ($inbound->count() === 1) {
                $match = $inbound->first();
            }
        }

        if (! is_array($match)) {
            return ['ok' => false, 'error' => 'Connected, but '.$this->preferredAgentLabel().' was not found in this workspace.'];
        }

        $phone = filled($match['phone_number'] ?? null) ? (string) $match['phone_number'] : $preferredPhone;
        $this->mergeSettings([
            'model_id' => $match['id'],
            'phone_number' => $phone,
            'agent_name' => $match['name'],
        ]);

        if (filled($phone)) {
            $this->firmService->current()->update(['twilio_phone_number' => $phone]);
        }

        $sync = $this->syncAgent($match['id']);

        return [
            'ok' => true,
            'model_id' => $match['id'],
            'name' => (string) $match['name'],
            'phone_number' => $phone,
            'error' => ($sync['ok'] ?? false) ? ($sync['error'] ?? null) : ('Agent linked, but sync failed: '.($sync['error'] ?? 'unknown error')),
        ];
    }

    private function preferredPhone(): ?string
    {
        $phone = trim((string) config('services.synthflow.agent_phone', ''));

        return $phone !== '' ? $phone : null;
    }

    private function preferredAgentLabel(): string
    {
        $name = trim((string) config('services.synthflow.agent_name', 'Qubetech AI Lawyer'));
        $phone = $this->preferredPhone();

        return $phone ? $name.' ('.$phone.')' : $name;
    }

    private function phonesMatch(string $left, string $right): bool
    {
        $normalize = fn (string $value) => ltrim(preg_replace('/\D+/', '', $value) ?? '', '0');
        $a = $normalize($left);
        $b = $normalize($right);
        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_ends_with($a, $b) || str_ends_with($b, $a);
    }

    /**
     * Create an inbound Synthflow agent for this firm and store model_id.
     *
     * @return array{ok: bool, model_id?: string, error?: string}
     */
    public function createInboundAgent(?string $name = null): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Connect Synthflow first (API key + workspace).'];
        }

        $firm = $this->firmService->current();
        $publicBase = rtrim((string) config('app.url'), '/');
        $agentName = trim((string) ($name ?: (($firm->receptionist_name ?: 'AI Receptionist').' · '.$firm->firm_name)));

        $agent = array_filter([
            'prompt' => $this->receptionist->systemPrompt(withCatalog: true),
            'greeting_message' => $this->receptionist->greetingMessage(),
            'greeting_message_mode' => 'agent_static',
            'language' => $this->mapLanguage((string) ($firm->ai_language ?: 'en')),
            'timezone' => $firm->resolvedTimezone(),
            'voice_id' => filled($firm->ai_voice) ? (string) $firm->ai_voice : null,
            'llm' => (string) config('services.synthflow.llm', 'gpt-4.1-mini'),
            'is_transcript_disabled' => false,
            // Conversational flow tuning: allow natural pauses, eliminate mid-sentence interruptions
            'patience_level' => 'high',
            'min_words_to_interrupt' => 3,
            'initial_pause_seconds' => 1,
            'transcriber_endpointing' => 300,
            'reminder_after_idle_time_seconds' => 8,
            'response_delay' => 1000,
            'llm_response_delay' => 600,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = [
            'type' => 'inbound',
            'name' => $agentName,
            'description' => 'AI Lawyer inbound receptionist for services, fees, and booking.',
            'external_webhook_url' => $publicBase.'/api/webhooks/finetuner/post-call',
            'inbound_call_webhook_url' => $publicBase.'/api/webhooks/finetuner/inbound',
            'is_recording' => (bool) $firm->recording_enabled,
            'agent' => $agent,
        ];

        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(25)
                ->post($connection['base_url'].'/assistants', $payload);
        } catch (\Throwable $exception) {
            Log::warning('Synthflow create agent failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach Synthflow to create an agent.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $modelId = (string) (
            data_get($response->json(), 'response.model_id')
            ?? data_get($response->json(), 'response.assistants.model_id')
            ?? data_get($response->json(), 'model_id')
            ?? ''
        );

        if ($modelId === '') {
            // Fallback: re-list and pick newest matching name
            $this->forgetSnapshotCache($connection['workspace_id'], $connection['api_key']);
            $agents = $this->listAgents($connection)['items'];
            $match = collect($agents)->first(fn ($agent) => ($agent['name'] ?? '') === $agentName);
            $modelId = (string) ($match['id'] ?? '');
        }

        if ($modelId === '') {
            return ['ok' => false, 'error' => 'Agent created but Synthflow did not return a model_id.'];
        }

        $this->mergeSettings([
            'model_id' => $modelId,
            'last_synced_at' => now()->toIso8601String(),
        ]);

        $this->forgetSnapshotCache($connection['workspace_id'], $connection['api_key']);

        $actions = $this->syncBookingActions(
            $connection,
            $modelId,
            $publicBase,
            (string) config('services.voice_gateway.secret'),
            (int) $firm->id,
        );

        if (! $actions['ok']) {
            return [
                'ok' => true,
                'model_id' => $modelId,
                'error' => 'Agent created, but some booking actions failed: '.($actions['error'] ?? ''),
            ];
        }

        $this->mergeSettings(['action_ids' => $actions['action_ids']]);

        return ['ok' => true, 'model_id' => $modelId];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function assignNumber(string $modelId, string $phoneNumber): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Synthflow is not connected.'];
        }

        $url = $connection['base_url'].'/assistants/'.rawurlencode($modelId);
        $payload = ['phone_number' => $phoneNumber];

        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(20)
                ->patch($url, $payload);

            if ($response->status() === 405 || $response->status() === 404) {
                $response = Http::withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(20)
                    ->put($url, $payload);
            }
        } catch (\Throwable $exception) {
            Log::warning('Synthflow assign failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach Synthflow.'];
        }

        $this->forgetSnapshotCache($connection['workspace_id'], $connection['api_key']);

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $this->mergeSettings([
            'model_id' => $modelId,
            'phone_number' => $phoneNumber,
        ]);

        $firm = $this->firmService->current();
        $firm->update(['twilio_phone_number' => $phoneNumber]);

        $sync = $this->syncAgent($modelId);

        return $sync['ok']
            ? ['ok' => true]
            : ['ok' => true, 'error' => 'Number attached, but agent sync warning: '.($sync['error'] ?? 'unknown')];
    }

    /**
     * Create the inbound agent if missing, otherwise update + sync tools.
     *
     * @return array{ok: bool, model_id?: string, error?: string}
     */
    public function ensureAgent(?string $name = null): array
    {
        $modelId = $this->settings()['model_id'] ?? null;
        if (is_string($modelId) && $modelId !== '') {
            $sync = $this->syncAgent($modelId);

            return $sync['ok']
                ? ['ok' => true, 'model_id' => $modelId, 'error' => $sync['error'] ?? null]
                : ['ok' => false, 'error' => $sync['error'] ?? 'Could not update Synthflow agent.'];
        }

        return $this->createInboundAgent($name);
    }

    /**
     * Place an outbound test call from the Synthflow agent to a phone number.
     *
     * @return array{ok: bool, error?: string}
     */
    public function placeTestCall(string $phone, string $name = 'Test caller'): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Synthflow is not connected.'];
        }

        $settings = $this->settings();
        $modelId = $settings['model_id'] ?? null;
        if (! is_string($modelId) || $modelId === '') {
            return ['ok' => false, 'error' => 'Create / update the Synthflow agent first.'];
        }

        $from = $settings['phone_number'] ?? $this->firmService->current()->twilio_phone_number;
        $payload = array_filter([
            'model_id' => $modelId,
            'phone' => $phone,
            'name' => $name,
            'from_phone_number' => filled($from) ? (string) $from : null,
            'prompt' => $this->receptionist->systemPrompt(withCatalog: true),
            'greeting' => $this->receptionist->greetingMessage(),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(25)
                ->post($connection['base_url'].'/calls', $payload);
        } catch (\Throwable $exception) {
            Log::warning('Synthflow test call failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not place Synthflow test call.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        return ['ok' => true];
    }

    /**
     * Push greeting, prompt, webhooks, and booking actions to the FineTuner agent.
     *
     * @return array{ok: bool, error?: string}
     */
    public function syncAgent(?string $modelId = null): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Synthflow is not connected.'];
        }

        $modelId = $modelId ?: ($this->settings()['model_id'] ?? config('services.synthflow.agent_id'));
        if (! is_string($modelId) || $modelId === '') {
            return ['ok' => false, 'error' => 'Create or attach a Synthflow agent first.'];
        }

        $firm = $this->firmService->current();
        $publicBase = rtrim((string) config('app.url'), '/');
        $secret = (string) config('services.voice_gateway.secret');

        $actions = $this->syncBookingActions($connection, $modelId, $publicBase, $secret, (int) $firm->id);

        $agent = array_filter([
            'prompt' => $this->receptionist->systemPrompt(withCatalog: true),
            'greeting_message' => $this->receptionist->greetingMessage(),
            'greeting_message_mode' => 'agent_static',
            'language' => $this->mapLanguage((string) ($firm->ai_language ?: 'en')),
            'timezone' => $firm->resolvedTimezone(),
            'voice_id' => filled($firm->ai_voice) ? (string) $firm->ai_voice : null,
            'is_transcript_disabled' => false,
            // Conversational flow tuning: allow natural pauses, eliminate mid-sentence interruptions
            'patience_level' => 'high',
            'min_words_to_interrupt' => 3,
            'initial_pause_seconds' => 1,
            'transcriber_endpointing' => 300,
            'reminder_after_idle_time_seconds' => 8,
            'response_delay' => 1000,
            'llm_response_delay' => 600,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = [
            'external_webhook_url' => $publicBase.'/api/webhooks/finetuner/post-call',
            'inbound_call_webhook_url' => $publicBase.'/api/webhooks/finetuner/inbound',
            'is_recording' => (bool) $firm->recording_enabled,
            'agent' => $agent,
        ];

        if (! empty($actions['action_ids'])) {
            $payload['actions'] = $actions['action_ids'];
            $payload['agent']['actions'] = $actions['action_ids'];
        }

        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(25)
                ->put($connection['base_url'].'/assistants/'.rawurlencode($modelId), $payload);

            if (! $response->successful()) {
                $response = Http::withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(25)
                    ->patch($connection['base_url'].'/assistants/'.rawurlencode($modelId), $payload);
            }
        } catch (\Throwable $exception) {
            Log::warning('Synthflow sync agent failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not sync agent prompt to Synthflow.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $this->mergeSettings([
            'model_id' => $modelId,
            'last_synced_at' => now()->toIso8601String(),
            'action_ids' => $actions['action_ids'],
        ]);

        $this->forgetSnapshotCache($connection['workspace_id'], $connection['api_key']);

        if (! $actions['ok']) {
            return ['ok' => true, 'error' => 'Prompt synced, but some booking actions failed: '.($actions['error'] ?? '')];
        }

        return ['ok' => true];
    }

    /**
     * @param  array{api_key: string, workspace_id: string, base_url: string}  $connection
     * @return array{ok: bool, error?: string, action_ids: list<string>}
     */
    private function syncBookingActions(array $connection, string $modelId, string $publicBase, string $secret, int $firmId): array
    {
        $definitions = [
            [
                'name' => 'get_services',
                'description' => 'List firm legal services and the lawyers who handle each service. Announce ONLY the service names and which lawyer handles each service. Do not mention prices at this initial stage.',
                'speech' => 'Let me pull up our legal services and lawyers for you.',
                'body' => ['firm_id' => $firmId, 'provider_call_id' => '<call_id>'],
                'variables' => [['name' => 'call_id', 'type' => 'string', 'description' => 'Current FineTuner call id', 'example' => 'call_123']],
            ],
            [
                'name' => 'get_service_details',
                'description' => 'Get the consultation fee, duration, and lawyers for a chosen service. Call this after the caller selects a service or asks about pricing, and announce the fee and duration at this point.',
                'speech' => 'One moment while I check the fee and details for that service.',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current FineTuner call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Numeric service id from get_services', 'example' => '1'],
                ],
            ],
            [
                'name' => 'get_lawyers',
                'description' => 'List lawyers for a service so you can tell the caller who will handle their matter.',
                'speech' => 'Let me see which lawyer handles that.',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current FineTuner call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Service id', 'example' => '1'],
                ],
            ],
            [
                'name' => 'find_available_slots',
                'description' => 'Check real-time appointment slots for a specific service, lawyer, and date. ALWAYS pass the numeric service_id obtained from get_services or get_service_details. Pass the numeric lawyer_id from get_lawyers. Call this BEFORE booking — never invent times.',
                'speech' => 'Let me check real-time availability for that date.',

                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                    'lawyer_id' => '<lawyer_id>',
                    'date' => '<date>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current FineTuner call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Service id', 'example' => '1'],
                    ['name' => 'lawyer_id', 'type' => 'string', 'description' => 'Lawyer id if known', 'example' => '2'],
                    ['name' => 'date', 'type' => 'string', 'description' => 'Preferred date YYYY-MM-DD', 'example' => '2026-10-10'],
                ],
            ],
            [
                'name' => 'book_legal_consultation',
                'description' => 'Book and confirm a legal consultation for the caller after agreeing date and time. Requires service_id, lawyer_id, start_at, name, phone. The appointment is immediately confirmed — NEVER ask for any 6-digit code or SMS verification code.',
                'speech' => 'I am booking that consultation for you now.',

                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                    'lawyer_id' => '<lawyer_id>',
                    'start_at' => '<start_at>',
                    'name' => '<name>',
                    'phone' => '<phone>',
                    'source' => 'phone_ai',
                ],
                'variables' => [
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Numeric service id from get_services or get_service_details', 'example' => '17'],
                    ['name' => 'lawyer_id', 'type' => 'string', 'description' => 'Numeric lawyer id from get_lawyers', 'example' => '17'],
                    ['name' => 'start_at', 'type' => 'string', 'description' => 'ISO start time from find_available_slots', 'example' => '2026-10-16T21:00:00+00:00'],
                    ['name' => 'name', 'type' => 'string', 'description' => 'Caller full name', 'example' => 'Ahmad Ali'],
                    ['name' => 'phone', 'type' => 'string', 'description' => 'Caller phone number in any format', 'example' => '03247087907'],
                ],
            ],
        ];

        $actionIds = [];
        $errors = [];

        foreach ($definitions as $definition) {
            $created = $this->createCustomAction($connection, $definition, $publicBase, $secret);
            if (! ($created['ok'] ?? false)) {
                $errors[] = $definition['name'].': '.($created['error'] ?? 'failed');

                continue;
            }
            $actionIds[] = (string) $created['action_id'];
        }

        if ($actionIds !== []) {
            try {
                // Detach all currently attached actions first to prevent duplicates and stale tool accumulation
                $assistantsRes = Http::withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(15)
                    ->get($connection['base_url'].'/assistants/'.rawurlencode($modelId).'?include_actions=true');

                $existingActions = (array) data_get($assistantsRes->json(), 'response.assistants.0.actions', []);
                if ($existingActions !== []) {
                    Http::withToken($connection['api_key'])
                        ->acceptJson()
                        ->timeout(15)
                        ->post($connection['base_url'].'/actions/detach', [
                            'model_id' => $modelId,
                            'actions' => $existingActions,
                        ]);
                }

                Http::withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(20)
                    ->post($connection['base_url'].'/actions/attach', [
                        'model_id' => $modelId,
                        'actions' => $actionIds,
                    ]);
            } catch (\Throwable $exception) {
                Log::warning('FineTuner attach actions failed', ['message' => $exception->getMessage()]);
                $errors[] = 'attach failed';
            }
        }

        return [
            'ok' => $errors === [],
            'error' => $errors === [] ? null : implode('; ', $errors),
            'action_ids' => $actionIds,
        ];
    }

    /**
     * @param  array{api_key: string, workspace_id: string, base_url: string}  $connection
     * @param  array{name: string, description: string, speech: string, body: array<string, mixed>, variables: list<array<string, string>>}  $definition
     * @return array{ok: bool, action_id?: string, error?: string}
     */
    private function createCustomAction(array $connection, array $definition, string $publicBase, string $secret): array
    {
        $headers = [
            ['key' => 'Content-Type', 'value' => 'application/json'],
            ['key' => 'Accept', 'value' => 'application/json'],
            ['key' => 'X-Firm-Id', 'value' => (string) ($definition['body']['firm_id'] ?? '')],
        ];
        if ($secret !== '') {
            $headers[] = ['key' => 'X-Voice-Gateway-Secret', 'value' => $secret];
        }

        $payload = [
            'CUSTOM_ACTION' => [
                'http_mode' => 'POST',
                'url' => $publicBase.'/api/ai/finetuner/tools/'.$definition['name'],
                'run_action_before_call_start' => false,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'speech_while_using_the_tool' => $definition['speech'],
                'headers' => $headers,
                'variables_during_the_call' => $definition['variables'],
                'json_body_stringified' => json_encode($definition['body'], JSON_UNESCAPED_SLASHES),
                'message_error' => 'I could not reach the booking system just now. Let me try another way or take a message.',
                'failure_timeout' => 20,
            ],
        ];

        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(20)
                ->post($connection['base_url'].'/actions', $payload);
        } catch (\Throwable $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $actionId = (string) (
            data_get($response->json(), 'response.action_id')
            ?? data_get($response->json(), 'action_id')
            ?? ''
        );

        if ($actionId === '') {
            return ['ok' => false, 'error' => 'No action_id returned'];
        }

        return ['ok' => true, 'action_id' => $actionId];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $integration = $this->integration();
        $stored = is_array($integration?->settings_json) ? $integration->settings_json : [];

        $defaults = array_filter([
            'workspace_id' => config('services.synthflow.workspace_id'),
            'base_url' => config('services.synthflow.base_url', 'https://api.us.synthflow.ai/v2'),
            'model_id' => config('services.synthflow.agent_id'),
            'phone_number' => config('services.synthflow.agent_phone'),
            'agent_name' => config('services.synthflow.agent_name', 'Qubetech AI Lawyer'),
        ], fn ($v) => $v !== null && $v !== '');

        return $this->settingsCache = array_merge($defaults, $stored);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function mergeSettings(array $settings): void
    {
        $firm = $this->firmService->current();
        if (! $firm) {
            return;
        }

        $integration = Integration::withoutGlobalScopes()->firstOrNew([
            'firm_id' => $firm->id,
            'provider' => 'finetuner',
        ]);
        if (! $integration->exists) {
            $integration->status = 'active';
        }

        $currentStored = is_array($integration->settings_json) ? $integration->settings_json : [];
        $merged = array_merge($this->settings(), $currentStored, $settings);
        $integration->settings_json = $merged;
        $integration->save();
        $this->settingsCache = $merged;
        $this->integrationCache = $integration;
    }

    private function integration(): ?Integration
    {
        if ($this->integrationCache !== false) {
            return $this->integrationCache;
        }

        return $this->integrationCache = Integration::query()->where('provider', 'finetuner')->first();
    }

    public function webhookUrls(): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return [
            'inbound' => $base.'/api/webhooks/finetuner/inbound',
            'post_call' => $base.'/api/webhooks/finetuner/post-call',
            'tool_base' => $base.'/api/ai/finetuner/tools',
            // Friendly aliases (same handlers)
            'inbound_alias' => $base.'/api/webhooks/synthflow/inbound',
            'post_call_alias' => $base.'/api/webhooks/synthflow/post-call',
            'tool_base_alias' => $base.'/api/ai/synthflow/tools',
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function probeConnection(string $apiKey, string $workspaceId, string $baseUrl): array
    {
        try {
            $agents = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(self::READ_TIMEOUT_SECONDS)
                ->connectTimeout(2)
                ->get($baseUrl.'/assistants/');

            if (! $agents->successful()) {
                return ['ok' => false, 'error' => $this->errorMessage($agents->json(), $agents->status())];
            }

            $numbers = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(self::READ_TIMEOUT_SECONDS)
                ->connectTimeout(2)
                ->get($baseUrl.'/numbers', [
                    'workspace' => $workspaceId,
                    'limit' => 1,
                ]);

            if (! $numbers->successful()) {
                return ['ok' => false, 'error' => 'API key works, but workspace ID looks wrong: '.$this->errorMessage($numbers->json(), $numbers->status())];
            }
        } catch (\Throwable $exception) {
            Log::warning('Synthflow probe failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach Synthflow API. Check region/base URL.'];
        }

        return ['ok' => true];
    }

    private function mapLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        return match ($language) {
            'en', 'en-us', 'english' => 'en-US',
            'ur', 'urdu' => 'en-US',
            default => strlen($language) >= 2 ? $language : 'en-US',
        };
    }

    /**
     * @param  array{api_key: string, workspace_id: string, base_url: string}  $connection
     * @return array{items: list<array<string, mixed>>, error: ?string}
     */
    private function listAgents(array $connection): array
    {
        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(self::READ_TIMEOUT_SECONDS)
                ->connectTimeout(2)
                ->get($connection['base_url'].'/assistants/');
        } catch (\Throwable $exception) {
            Log::warning('Synthflow agents unreachable', ['message' => $exception->getMessage()]);

            return ['items' => [], 'error' => 'Could not reach Synthflow.'];
        }

        if (! $response->successful()) {
            return ['items' => [], 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $items = data_get($response->json(), 'response.assistants', []);
        if (isset($items['model_id']) || isset($items['name'])) {
            $items = [$items];
        }

        $agents = [];
        foreach (is_array($items) ? $items : [] as $agent) {
            if (! is_array($agent)) {
                continue;
            }
            $agents[] = [
                'id' => (string) ($agent['model_id'] ?? $agent['id'] ?? ''),
                'name' => (string) ($agent['name'] ?? 'Untitled agent'),
                'description' => (string) ($agent['description'] ?? ''),
                'phone_number' => (string) ($agent['phone_number'] ?? ''),
                'type' => (string) ($agent['type'] ?? 'inbound'),
                'voice' => (string) data_get($agent, 'agent.voice_id', data_get($agent, 'details.voice', '')),
            ];
        }

        return ['items' => array_values(array_filter($agents, fn ($agent) => $agent['id'] !== '')), 'error' => null];
    }

    /**
     * @param  array{api_key: string, workspace_id: string, base_url: string}  $connection
     * @return array{items: list<array<string, mixed>>, error: ?string}
     */
    private function listNumbers(array $connection): array
    {
        try {
            $response = Http::withToken($connection['api_key'])
                ->acceptJson()
                ->timeout(self::READ_TIMEOUT_SECONDS)
                ->connectTimeout(2)
                ->get($connection['base_url'].'/numbers', [
                    'workspace' => $connection['workspace_id'],
                    'limit' => 50,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Synthflow numbers unreachable', ['message' => $exception->getMessage()]);

            return ['items' => [], 'error' => 'Could not reach Synthflow.'];
        }

        if (! $response->successful()) {
            return ['items' => [], 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $items = data_get($response->json(), 'response.phone_numbers', data_get($response->json(), 'response.numbers', []));
        if (isset($items['number'])) {
            $items = [$items];
        }

        $numbers = [];
        foreach (is_array($items) ? $items : [] as $number) {
            if (! is_array($number)) {
                continue;
            }
            $value = (string) ($number['number'] ?? $number['phone_number'] ?? '');
            if ($value === '') {
                continue;
            }
            $numbers[] = [
                'number' => $value,
                'available' => (bool) ($number['is_available'] ?? false),
            ];
        }

        return ['items' => $numbers, 'error' => null];
    }

    private function errorMessage(mixed $json, int $status): string
    {
        $message = is_array($json)
            ? ($json['message'] ?? $json['error'] ?? $json['detail'] ?? null)
            : null;

        if (is_array($message)) {
            $message = $message['description'] ?? json_encode($message);
        }

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'Synthflow returned an error ('.$status.').';
    }

    /**
     * @param  array{api_key: string, workspace_id: string, base_url: string}  $connection
     */
    private function snapshotCacheKey(array $connection): string
    {
        return 'finetuner.snapshot.'.md5($connection['workspace_id'].'|'.substr($connection['api_key'], -8));
    }

    private function forgetSnapshotCache(string $workspaceId, ?string $apiKey = null): void
    {
        Cache::forget('finetuner.snapshot.'.md5($workspaceId));

        if (is_string($apiKey) && $apiKey !== '') {
            Cache::forget($this->snapshotCacheKey([
                'api_key' => $apiKey,
                'workspace_id' => $workspaceId,
                'base_url' => '',
            ]));
        }
    }
}

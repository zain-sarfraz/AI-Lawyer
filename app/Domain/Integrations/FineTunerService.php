<?php

declare(strict_types=1);

namespace App\Domain\Integrations;

use App\Domain\AI\AiReceptionistService;
use App\Domain\Firm\FirmService;
use App\Models\Integration;
use App\Models\Lawyer;
use App\Models\Service;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FineTunerService
{
    private const READ_TIMEOUT_SECONDS = 12;

    private const CONNECT_TIMEOUT_SECONDS = 5;

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
        return $this->connection() !== null || filled($this->settings()['model_id'] ?? null);
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
        $modelId = $settings['model_id'] ?? null;
        $phone = $settings['phone_number'] ?? null;
        $agentName = (string) ($settings['agent_name'] ?? 'Synthflow Agent');

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
            'connected' => $connection !== null || filled($modelId),
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

        $apiKey = $stored['api_key'] ?? null;
        $workspaceId = $settings['workspace_id'] ?? null;
        $baseUrl = rtrim((string) ($settings['base_url'] ?? config('services.synthflow.base_url', 'https://api.us.synthflow.ai/v2')), '/');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($workspaceId) || $workspaceId === '') {
            return $this->connectionCache = null;
        }

        return $this->connectionCache = [
            'api_key' => $apiKey,
            'workspace_id' => $workspaceId,
            'base_url' => $baseUrl !== '' ? $baseUrl : 'https://api.us.synthflow.ai/v2',
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
            $modelId = $settings['model_id'] ?? null;
            $phone = $settings['phone_number'] ?? null;
            $agentName = (string) ($settings['agent_name'] ?? 'Synthflow Agent');
            $hasModel = filled($modelId);

            return [
                'connected' => false,
                'error' => null,
                'agents' => $hasModel ? [[
                    'id' => (string) $modelId,
                    'name' => $agentName,
                    'description' => '',
                    'phone_number' => is_string($phone) ? (string) $phone : '',
                    'type' => 'inbound',
                    'voice' => '',
                ]] : [],
                'numbers' => filled($phone) ? [['number' => (string) $phone, 'available' => true]] : [],
                'model_id' => is_string($modelId) ? (string) $modelId : null,
                'phone_number' => is_string($phone) ? (string) $phone : null,
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
        $this->extendExecutionTime();
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

        $this->connectionCache = false;
        $this->settingsCache = null;
        $this->integrationCache = false;
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

        $agents = $listed['items'];
        $currentId = $this->settings()['model_id'] ?? null;

        $match = null;
        if (is_string($currentId) && $currentId !== '') {
            $match = collect($agents)->first(fn (array $agent) => $agent['id'] === $currentId);
        }
        if ($match === null) {
            $inbound = collect($agents)->filter(fn (array $agent) => ($agent['type'] ?? 'inbound') === 'inbound')->values();
            if ($inbound->count() === 1) {
                $match = $inbound->first();
            }
        }

        if (! is_array($match)) {
            return ['ok' => false, 'error' => 'Connected. Select an existing Synthflow agent from the list and click "Use this agent".'];
        }

        $phone = filled($match['phone_number'] ?? null) ? (string) $match['phone_number'] : null;
        $matchVoice = trim((string) ($match['voice'] ?? ''));
        $settingsToMerge = [
            'model_id' => $match['id'],
            'phone_number' => $phone,
            'agent_name' => $match['name'],
        ];
        if ($matchVoice !== '' && $matchVoice !== \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID) {
            $settingsToMerge['agent_voice_id'] = $matchVoice;
        }
        $this->mergeSettings($settingsToMerge);

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

    /**
     * Disabled: never create new agents on Synthflow from this application.
     *
     * @return array{ok: bool, model_id?: string, error?: string}
     */
    public function createInboundAgent(?string $name = null): array
    {
        return [
            'ok' => false,
            'error' => 'Creating new Synthflow agents is disabled. Connect your workspace and select an existing Synthflow agent instead.',
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function assignNumber(string $modelId, string $phoneNumber): array
    {
        $this->extendExecutionTime();
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
     * Update + sync the already-attached inbound agent. Never creates a new agent on Synthflow.
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

        return [
            'ok' => false,
            'error' => 'No Synthflow agent attached. Select an existing Synthflow agent instead — new agents are never auto-created.',
        ];
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
            'phone_number_to' => $phone,
            'name' => $name,
            'from_phone_number' => filled($from) ? (string) $from : null,
            'phone_number_from' => filled($from) ? (string) $from : null,
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
        $this->extendExecutionTime();

        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Synthflow API key is missing. Set SYNTHFLOW_API_KEY in .env or connect your workspace to sync live to Synthflow.'];
        }

        $modelId = $modelId ?: ($this->settings()['model_id'] ?? null);
        if (! is_string($modelId) || $modelId === '') {
            return ['ok' => false, 'error' => 'Attach an existing Synthflow agent first.'];
        }

        $firm = $this->firmService->current();
        $publicBase = rtrim((string) config('app.url'), '/');
        $secret = (string) config('services.voice_gateway.secret');

        // Keep First message visible in CRM and spoken on calls after every sync.
        $greeting = $this->receptionist->greetingMessage($firm);

        $actions = $this->syncBookingActions($connection, $modelId, $publicBase, $secret, (int) $firm->id);

        $listening = array_filter(
            $this->agentListeningConfig($firm),
            fn ($value) => $value !== null && $value !== ''
        );

        // Always force agent-speaks-first. Do not let array_filter drop greeting fields.
        $agent = array_merge($listening, [
            'prompt' => $this->receptionist->systemPrompt(withCatalog: true),
            'greeting_message' => $greeting,
            'greeting_message_mode' => 'agent_static',
            'timezone' => $firm->resolvedTimezone(),
            'is_transcript_disabled' => false,
        ]);

        $liveVoiceId = trim((string) ($actions['current_voice_id'] ?? ''));
        $storedAgentVoiceId = trim((string) ($this->settings()['agent_voice_id'] ?? ''));
        if ($liveVoiceId !== '' && $liveVoiceId !== \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID) {
            $storedAgentVoiceId = $liveVoiceId;
        }

        if (filled($firm->ai_voice)) {
            $agent['voice_id'] = (string) $firm->ai_voice;
        } elseif ($this->receptionist->isUrduLanguage($firm->ai_language)) {
            $agent['voice_id'] = \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID;
        } elseif ($storedAgentVoiceId !== '' && $storedAgentVoiceId !== \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID) {
            // English restores the Synthflow agent's own voice instead of the Urdu voice.
            $agent['voice_id'] = $storedAgentVoiceId;
        }

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
        } catch (\Throwable $exception) {
            Log::warning('Synthflow sync agent failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not sync agent prompt to Synthflow.'];
        }

        if (! $response->successful()) {
            $error = $this->errorMessage($response->json(), $response->status());

            // Voicemail "human speaks first" mode blocks agent_static greetings — retry without conflicting fields.
            if (str_contains(strtolower($error), 'voicemail') || str_contains(strtolower($error), 'greeting_message_mode')) {
                $payload['agent']['greeting_message_mode'] = 'agent_static';
                $payload['agent']['greeting_message'] = $greeting;
                unset($payload['voicemail_message']);

                $retry = Http::withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(25)
                    ->put($connection['base_url'].'/assistants/'.rawurlencode($modelId), $payload);

                if (! $retry->successful()) {
                    return ['ok' => false, 'error' => $this->errorMessage($retry->json(), $retry->status())];
                }
            } else {
                return ['ok' => false, 'error' => $error];
            }
        }

        $merged = [
            'model_id' => $modelId,
            'last_synced_at' => now()->toIso8601String(),
            'action_ids' => $actions['action_ids'],
            'greeting_message' => $greeting,
            'greeting_message_mode' => 'agent_static',
            'firm_id' => (int) $firm->id,
            'account_label' => $firm->isFirmAccount() ? 'firm' : 'individual',
        ];
        if ($storedAgentVoiceId !== '') {
            $merged['agent_voice_id'] = $storedAgentVoiceId;
        }
        $this->mergeSettings($merged);

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
                'description' => 'List firm legal services by name. Read the "speak" field aloud. Do NOT use option numbers. Ask which service they want. Map the spoken service name to services[].id. Do not mention prices yet.',
                'speech' => '',
                'body' => ['firm_id' => $firmId, 'provider_call_id' => '<call_id>'],
                'variables' => [['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123']],
            ],
            [
                'name' => 'get_service_details',
                'description' => 'Get the consultation fee, duration (if set), and lawyers for a chosen service. Read the "speak" field aloud.',
                'speech' => '',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Numeric service id from get_services', 'example' => '1'],
                ],
            ],
            [
                'name' => 'get_lawyers',
                'description' => 'List active lawyers for a service with their weekly availability hours.',
                'speech' => '',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Service id', 'example' => '1'],
                ],
            ],
            [
                'name' => 'list_available_days',
                'description' => 'List available days this week by weekday name. Read "speak" aloud. Ask which day. Do NOT use option numbers. Never invent days.',
                'speech' => '',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                    'lawyer_id' => '<lawyer_id>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Service id', 'example' => '1'],
                    ['name' => 'lawyer_id', 'type' => 'string', 'description' => 'Lawyer id from get_lawyers', 'example' => '2'],
                ],
            ],
            [
                'name' => 'find_available_slots',
                'description' => 'Get availability for one date. Tell ONLY the available_from to available_until window. Let the caller choose any time in that window. Do NOT list every slot or use option numbers. Pass service_id, lawyer_id, date YYYY-MM-DD.',
                'speech' => '',
                'body' => [
                    'firm_id' => $firmId,
                    'provider_call_id' => '<call_id>',
                    'service_id' => '<service_id>',
                    'lawyer_id' => '<lawyer_id>',
                    'date' => '<date>',
                ],
                'variables' => [
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Service id', 'example' => '1'],
                    ['name' => 'lawyer_id', 'type' => 'string', 'description' => 'Lawyer id', 'example' => '2'],
                    ['name' => 'date', 'type' => 'string', 'description' => 'Date YYYY-MM-DD from list_available_days', 'example' => '2026-10-10'],
                ],
            ],
            [
                'name' => 'book_legal_consultation',
                'description' => 'Book ONE legal consultation after the caller confirms the summary. Map their time to slots[].start_at from find_available_slots. Call AT MOST ONCE per call. Pass "same" as phone if the caller refers to the number they are calling from.',
                'speech' => '',

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
                    ['name' => 'call_id', 'type' => 'string', 'description' => 'Current call id', 'example' => 'call_123'],
                    ['name' => 'service_id', 'type' => 'string', 'description' => 'Numeric service id from get_services or get_service_details', 'example' => '1'],
                    ['name' => 'lawyer_id', 'type' => 'string', 'description' => 'Numeric lawyer id from get_lawyers', 'example' => '2'],
                    ['name' => 'start_at', 'type' => 'string', 'description' => 'ISO start time from find_available_slots', 'example' => '2026-10-16T21:00:00+00:00'],
                    ['name' => 'name', 'type' => 'string', 'description' => 'Caller full name', 'example' => 'Alex Smith'],
                    ['name' => 'phone', 'type' => 'string', 'description' => 'Caller phone number, or "same" if using their current number', 'example' => '+15550100001'],
                ],
            ],
        ];

        $actionIds = [];
        $errors = [];

        $responses = Http::pool(function (Pool $pool) use ($connection, $definitions, $modelId, $publicBase, $secret) {
            $requests = [
                $pool->as('assistant')
                    ->withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(15)
                    ->get($connection['base_url'].'/assistants/'.rawurlencode($modelId).'?include_actions=true'),
            ];

            foreach ($definitions as $definition) {
                $requests[] = $pool->as($definition['name'])
                    ->withToken($connection['api_key'])
                    ->acceptJson()
                    ->timeout(20)
                    ->post($connection['base_url'].'/actions', $this->customActionPayload($definition, $publicBase, $secret));
            }

            return $requests;
        });

        foreach ($definitions as $definition) {
            $name = $definition['name'];
            $res = $responses[$name] ?? null;

            if ($res instanceof \Throwable) {
                $errors[] = $name.': '.$res->getMessage();

                continue;
            }
            if (! $res instanceof Response) {
                $errors[] = $name.': no response';

                continue;
            }
            if (! $res->successful()) {
                $errors[] = $name.': '.$this->errorMessage($res->json(), $res->status());

                continue;
            }

            $actionId = (string) (
                data_get($res->json(), 'response.action_id')
                ?? data_get($res->json(), 'action_id')
                ?? ''
            );
            if ($actionId === '') {
                $errors[] = $name.': No action_id returned';

                continue;
            }

            $actionIds[] = $actionId;
        }

        $assistantsRes = $responses['assistant'] ?? null;
        $currentVoiceId = $assistantsRes instanceof Response
            ? trim((string) data_get($assistantsRes->json(), 'response.assistants.0.agent.voice_id', ''))
            : '';

        if ($actionIds !== []) {
            try {
                // Detach all currently attached actions first to prevent duplicates and stale tool accumulation
                $existingActions = $assistantsRes instanceof Response
                    ? (array) data_get($assistantsRes->json(), 'response.assistants.0.actions', [])
                    : [];

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
            'current_voice_id' => $currentVoiceId !== '' ? $currentVoiceId : null,
        ];
    }

    /**
     * @param  array{name: string, description: string, speech: string, body: array<string, mixed>, variables: list<array<string, string>>}  $definition
     * @return array<string, mixed>
     */
    private function customActionPayload(array $definition, string $publicBase, string $secret): array
    {
        $headers = [
            ['key' => 'Content-Type', 'value' => 'application/json'],
            ['key' => 'Accept', 'value' => 'application/json'],
            ['key' => 'X-Firm-Id', 'value' => (string) ($definition['body']['firm_id'] ?? '')],
        ];
        if ($secret !== '') {
            $headers[] = ['key' => 'X-Voice-Gateway-Secret', 'value' => $secret];
        }

        return [
            'CUSTOM_ACTION' => [
                'http_mode' => 'POST',
                'url' => $publicBase.'/api/ai/finetuner/tools/'.$definition['name'],
                'run_action_before_call_start' => false,
                'name' => $definition['name'],
                'description' => $definition['description'],
                // Stay silent while running the tool.
                'speech_while_using_the_tool' => $definition['speech'],
                'message_delay' => '',
                'message_delay_time' => 60,
                'action_sound' => '',
                'headers' => $headers,
                'variables_during_the_call' => $definition['variables'],
                'json_body_stringified' => json_encode($definition['body'], JSON_UNESCAPED_SLASHES),
                'message_error' => 'I could not reach the booking system just now. Let me try another way or take a message.',
                'failure_timeout' => 20,
            ],
        ];
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

        $defaults = [
            'base_url' => (string) config('services.synthflow.base_url', 'https://api.us.synthflow.ai/v2'),
        ];

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
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->get($baseUrl.'/assistants/');

            if (! $agents->successful()) {
                return ['ok' => false, 'error' => $this->errorMessage($agents->json(), $agents->status())];
            }

            $numbers = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(self::READ_TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
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

    /**
     * Listening and speech-to-text settings so the agent catches caller speech
     * (names, phone numbers, legal terms) without cutting them off.
     *
     * @return array<string, mixed>
     */
    private function agentListeningConfig(\App\Models\Firm $firm): array
    {
        $language = $this->mapLanguage((string) ($firm->ai_language ?: 'en'));
        $firmId = (int) $firm->id;
        $serviceNames = Service::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(40)
            ->pluck('name');
        $lawyerNames = Lawyer::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->with('user')
            ->orderBy('id')
            ->limit(40)
            ->get()
            ->map(fn (Lawyer $lawyer) => $lawyer->displayName());

        $keywords = collect([
            'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'thirty', 'book', 'appointment', 'consultation',
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            'morning', 'afternoon', 'evening', 'AM', 'PM',
        ])
            ->merge($serviceNames)
            ->merge($lawyerNames)
            ->merge($serviceNames->flatMap(function (string $name) {
                // Help STT catch individual words from service titles.
                return preg_split('/\s+/', strtolower($name)) ?: [];
            }))
            ->filter(fn ($word) => is_string($word) && trim($word) !== '')
            ->map(fn (string $word) => trim($word))
            ->unique()
            ->values()
            ->take(80)
            ->all();

        return [
            'language' => $language,
            // Wait for natural pauses — callers dictate names / phone slowly.
            'patience_level' => 'high',
            'min_words_to_interrupt' => 3,
            'initial_pause_seconds' => 1,
            'transcriber_endpointing' => 300,
            'transcriber_speech_recognition' => 'high_accuracy',
            'transcriber_provider' => 'synthflow-stt',
            // Extreme voice-isolation chops caller audio; standard catches speech better.
            'noise_cancellation' => 'standard',
            'suppression_level' => 0.35,
            'send_user_idle_reminders' => true,
            'reminder_after_idle_time_seconds' => 12,
            'response_delay' => 900,
            'llm_response_delay' => 500,
            'transcriber_keywords' => $keywords !== [] ? $keywords : null,
        ];
    }

    private function mapLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        return match ($language) {
            'ur', 'urdu', 'ur-pk' => 'ur',   // Synthflow Urdu
            'en', 'en-us', 'english', '' => 'en-US',
            default => 'en-US',
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
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
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
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
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

    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }
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

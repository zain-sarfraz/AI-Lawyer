<?php

namespace App\Domain\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FineTunerService
{
    public function __construct(private readonly IntegrationCredentialService $credentials) {}

    public function configured(): bool
    {
        return $this->connection() !== null;
    }

    /**
     * @return array{api_key: string, workspace_id: string, base_url: string}|null
     */
    public function connection(): ?array
    {
        $integration = Integration::query()->where('provider', 'finetuner')->where('status', 'active')->first();
        $stored = $integration?->encrypted_credentials ?? [];
        $settings = $integration?->settings_json ?? [];

        $apiKey = $stored['api_key'] ?? config('services.finetuner.api_key');
        $workspaceId = $settings['workspace_id'] ?? config('services.finetuner.workspace_id');
        $baseUrl = rtrim((string) ($settings['base_url'] ?? config('services.finetuner.base_url')), '/');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($workspaceId) || $workspaceId === '') {
            return null;
        }

        return [
            'api_key' => $apiKey,
            'workspace_id' => $workspaceId,
            'base_url' => $baseUrl !== '' ? $baseUrl : 'https://api.synthflow.ai/v2',
        ];
    }

    public function maskedKey(): ?string
    {
        $integration = Integration::query()->where('provider', 'finetuner')->first();

        return $integration?->maskedCredentials()['api_key'] ?? null;
    }

    /**
     * @return array{connected: bool, error: ?string, agents: list<array<string, mixed>>, numbers: list<array<string, mixed>>}
     */
    public function snapshot(): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['connected' => false, 'error' => null, 'agents' => [], 'numbers' => []];
        }

        return Cache::remember('finetuner.snapshot.'.md5($connection['workspace_id']), 45, function () use ($connection) {
            $agents = $this->listAgents($connection);
            $numbers = $this->listNumbers($connection);
            $error = $agents['error'] ?? $numbers['error'] ?? null;

            return [
                'connected' => $error === null,
                'error' => $error,
                'agents' => $agents['items'],
                'numbers' => $numbers['items'],
            ];
        });
    }

    public function connect(string $apiKey, string $workspaceId, ?string $baseUrl = null): void
    {
        $this->credentials->upsert('finetuner', [
            'api_key' => $apiKey,
        ], [
            'workspace_id' => $workspaceId,
            'base_url' => $baseUrl ?: 'https://api.synthflow.ai/v2',
        ]);

        Cache::forget('finetuner.snapshot.'.md5($workspaceId));
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function assignNumber(string $modelId, string $phoneNumber): array
    {
        $connection = $this->connection();
        if ($connection === null) {
            return ['ok' => false, 'error' => 'FineTuner is not connected.'];
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
            Log::warning('FineTuner assign failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach FineTuner.'];
        }

        Cache::forget('finetuner.snapshot.'.md5($connection['workspace_id']));

        if (! $response->successful()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? $response->json('detail')
                ?? 'FineTuner rejected the phone assignment.';

            return ['ok' => false, 'error' => is_string($message) ? $message : 'FineTuner rejected the phone assignment.'];
        }

        return ['ok' => true];
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
                ->timeout(12)
                ->get($connection['base_url'].'/assistants/');
        } catch (\Throwable $exception) {
            Log::warning('FineTuner agents unreachable', ['message' => $exception->getMessage()]);

            return ['items' => [], 'error' => 'Could not reach FineTuner.'];
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
                ->timeout(12)
                ->get($connection['base_url'].'/numbers', [
                    'workspace' => $connection['workspace_id'],
                    'limit' => 50,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('FineTuner numbers unreachable', ['message' => $exception->getMessage()]);

            return ['items' => [], 'error' => 'Could not reach FineTuner.'];
        }

        if (! $response->successful()) {
            return ['items' => [], 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $items = data_get($response->json(), 'response.numbers', data_get($response->json(), 'response.phone_numbers', []));
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

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'FineTuner returned an error ('.$status.').';
    }
}

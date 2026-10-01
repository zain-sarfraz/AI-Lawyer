<?php

namespace App\Domain\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    public function apiKey(): ?string
    {
        $key = config('services.openai.api_key');
        if ($key) {
            return $key;
        }

        $integration = Integration::query()->where('provider', 'openai')->where('status', 'active')->first();

        return $integration?->encrypted_credentials['api_key'] ?? null;
    }

    public function realtimeModel(): string
    {
        return (string) config('services.openai.realtime_model', 'gpt-4o-realtime-preview');
    }

    /**
     * Generate a short post-call summary from transcript text.
     */
    public function summarizeTranscript(string $transcript): ?string
    {
        $apiKey = $this->apiKey();
        if (! $apiKey || trim($transcript) === '') {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.summary_model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Summarize this law-firm receptionist call in 3-5 sentences. Include caller intent, service interest, booking outcome, and follow-up needs. Do not invent facts.',
                        ],
                        ['role' => 'user', 'content' => $transcript],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI summary failed', ['status' => $response->status()]);

                return null;
            }

            return trim((string) data_get($response->json(), 'choices.0.message.content'));
        } catch (\Throwable $e) {
            Log::error('OpenAI summary exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}

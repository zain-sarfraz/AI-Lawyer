<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\AI\AiToolRegistry;
use App\Domain\Firm\FirmContext;
use App\Http\Controllers\Controller;
use App\Models\AiSession;
use App\Models\Call;
use App\Models\Firm;
use App\Models\Scopes\FirmScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints FineTuner/Synthflow custom actions call during a live conversation.
 */
class FineTunerToolController extends Controller
{
    public function __construct(private readonly AiToolRegistry $tools) {}

    public function invoke(Request $request, string $tool): JsonResponse
    {
        $data = $request->validate([
            'provider_call_id' => ['nullable', 'string'],
            'call_id' => ['nullable', 'string'],
            'firm_id' => ['nullable', 'integer'],
            'arguments' => ['nullable', 'array'],
        ]);

        $providerCallId = (string) ($data['provider_call_id'] ?? $data['call_id'] ?? '');
        $arguments = $data['arguments'] ?? [];

        if ($tool === 'book_legal_consultation') {
            $tool = 'book_appointment';
        }

        // Allow FineTuner to send flat fields instead of nested arguments.
        foreach ($request->except(['provider_call_id', 'call_id', 'firm_id', 'arguments', 'tool']) as $key => $value) {
            if (! array_key_exists($key, $arguments)) {
                $arguments[$key] = $value;
            }
        }

        $firmId = $this->resolveFirmId($request, $data, $providerCallId);
        \Illuminate\Support\Facades\Log::info('FineTuner tool call received', [
            'tool' => $tool,
            'firm_id' => $firmId,
            'provider_call_id' => $providerCallId,
            'arguments' => $arguments,
        ]);

        if ($firmId === null) {
            \Illuminate\Support\Facades\Log::warning('FineTuner tool call: firm could not be resolved', [
                'tool' => $tool,
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'FIRM_REQUIRED', 'message' => 'Could not resolve firm context.'], 422);
        }

        return app(FirmContext::class)->run($firmId, function () use ($tool, $arguments, $providerCallId, $firmId) {
            $session = null;
            if ($providerCallId !== '') {
                $call = Call::query()->withoutGlobalScope(FirmScope::class)
                    ->where('provider_call_id', $providerCallId)
                    ->first();

                if (! $call && $firmId) {
                    $call = Call::query()->create([
                        'firm_id' => $firmId,
                        'provider_call_id' => $providerCallId,
                        'direction' => 'inbound',
                        'caller_phone' => $arguments['phone'] ?? null,
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                }

                if ($call && ! empty($arguments['phone']) && empty($call->caller_phone)) {
                    $call->update(['caller_phone' => $arguments['phone']]);
                }

                $session = $call?->aiSession;
                if (! $session && $call) {
                    $session = AiSession::query()->where('call_id', $call->id)->first();
                    if (! $session) {
                        $session = AiSession::query()->create([
                            'firm_id' => $firmId,
                            'call_id' => $call->id,
                            'channel' => 'phone',
                            'state' => ['source' => 'finetuner'],
                        ]);
                    }
                }

                // Ensure book_appointment always receives the live call id.
                if ($call && empty($arguments['call_id'])) {
                    $arguments['call_id'] = $call->id;
                }
                if ($providerCallId !== '' && empty($arguments['provider_call_id'])) {
                    $arguments['provider_call_id'] = $providerCallId;
                }
                if ($tool === 'book_appointment') {
                    $arguments['source'] = 'phone_ai';
                }
            }

            $result = $this->tools->invoke($tool, $arguments, $session);

            return response()->json(['data' => $result]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveFirmId(Request $request, array $data, string $providerCallId): ?int
    {
        // 1. Explicit firm_id in data, input body, or query
        $rawFirmId = $data['firm_id'] ?? $request->input('firm_id') ?? $request->query('firm_id');
        if (! empty($rawFirmId) && is_numeric($rawFirmId)) {
            return (int) $rawFirmId;
        }

        // 2. X-Firm-Id header
        $header = $request->header('X-Firm-Id');
        if (is_numeric($header)) {
            return (int) $header;
        }

        // 3. Provider call id in Call table
        if ($providerCallId !== '') {
            $callFirm = Call::query()->withoutGlobalScope(FirmScope::class)
                ->where('provider_call_id', $providerCallId)
                ->value('firm_id');
            if ($callFirm) {
                return (int) $callFirm;
            }
        }

        // 4. Model / Assistant ID lookup in Integration table or config
        $modelId = (string) (
            $request->input('model_id')
            ?? $request->input('assistant_id')
            ?? $request->input('agent_id')
            ?? config('services.synthflow.agent_id')
            ?? ''
        );
        if ($modelId !== '') {
            $integration = \App\Models\Integration::query()
                ->withoutGlobalScopes()
                ->where('provider', 'finetuner')
                ->where('status', 'active')
                ->latest('id')
                ->get()
                ->first(fn ($row) => (string) data_get($row->settings_json, 'model_id') === $modelId);

            if ($integration?->firm_id) {
                return (int) $integration->firm_id;
            }
        }

        // 5. Match by phone number (called number or caller number)
        $phone = (string) (
            $request->input('to_number')
            ?? $request->input('phone_number')
            ?? $request->input('to')
            ?? config('services.synthflow.agent_phone')
            ?? ''
        );
        if ($phone !== '') {
            $firmByPhone = Firm::query()->where('twilio_phone_number', $phone)->latest('id')->value('id');
            if ($firmByPhone) {
                return (int) $firmByPhone;
            }
        }

        // 6. Active Synthflow integration fallback
        $activeFinetunerFirmId = \App\Models\Integration::query()
            ->withoutGlobalScopes()
            ->where('provider', 'finetuner')
            ->where('status', 'active')
            ->latest('id')
            ->value('firm_id');
        if ($activeFinetunerFirmId) {
            return (int) $activeFinetunerFirmId;
        }

        // 7. Local/testing fallback: latest firm with active services, or first firm
        if (app()->environment('local', 'testing')) {
            $firmWithServices = Firm::query()
                ->whereHas('services')
                ->orderByDesc('id')
                ->value('id');

            return (int) ($firmWithServices ?: Firm::query()->orderBy('id')->value('id'));
        }

        return null;
    }
}

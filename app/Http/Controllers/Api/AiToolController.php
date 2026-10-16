<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\AI\AiReceptionistService;
use App\Domain\AI\AiToolDefinitions;
use App\Domain\AI\AiToolRegistry;
use App\Domain\Calls\CallService;
use App\Domain\Firm\FirmContext;
use App\Http\Controllers\Controller;
use App\Models\AiSession;
use App\Models\Call;
use App\Models\Firm;
use App\Models\Scopes\FirmScope;
use App\Support\CallTranscript;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiToolController extends Controller
{
    public function __construct(
        private readonly AiToolRegistry $tools,
        private readonly AiReceptionistService $receptionist,
        private readonly CallService $calls,
    ) {}

    public function context(): JsonResponse
    {
        return $this->withResolvedFirm(null, function () {
            return response()->json([
                'system_prompt' => $this->receptionist->systemPrompt(),
                'session_context' => $this->receptionist->sessionContext(),
                'tools' => $this->tools->toolNames(),
                'realtime_tools' => AiToolDefinitions::realtimeTools(),
                'realtime_model' => config('services.openai.realtime_model'),
            ]);
        });
    }

    public function invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tool' => ['required', 'string'],
            'arguments' => ['nullable', 'array'],
            'ai_session_id' => ['nullable', 'exists:ai_sessions,id'],
            'provider_call_id' => ['nullable', 'string'],
        ]);

        $call = null;
        if (! empty($data['provider_call_id'])) {
            $call = Call::query()->withoutGlobalScope(FirmScope::class)
                ->where('provider_call_id', $data['provider_call_id'])
                ->first();
        }

        return $this->withResolvedFirm($call?->firm_id, function () use ($data, $call) {
            $session = null;
            if (! empty($data['ai_session_id'])) {
                $session = AiSession::query()->find($data['ai_session_id']);
            } elseif ($call) {
                $session = $call->aiSession;
            }

            $result = $this->tools->invoke($data['tool'], $data['arguments'] ?? [], $session);

            return response()->json(['data' => $result]);
        });
    }

    public function completeCall(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_call_id' => ['required', 'string'],
            'transcript' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'intent' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer'],
            'provider_session_id' => ['nullable', 'string'],
        ]);

        $call = Call::query()->withoutGlobalScope(FirmScope::class)
            ->where('provider_call_id', $data['provider_call_id'])
            ->firstOrFail();

        return app(FirmContext::class)->run((int) $call->firm_id, function () use ($call, $data) {
            if (! empty($data['provider_session_id']) && $call->aiSession) {
                $call->aiSession->update([
                    'provider_session_id' => $data['provider_session_id'],
                ]);
            }

            if (! empty($data['transcript'])) {
                $data['transcript'] = CallTranscript::normalize($data['transcript']);
            }

            $completed = $this->calls->complete($call, $data);

            return response()->json(['data' => $completed->load('aiSession')]);
        });
    }

    private function withResolvedFirm(?int $firmId, callable $callback): JsonResponse
    {
        $firmId ??= app(FirmContext::class)->id();
        if ($firmId === null && app()->environment('local', 'testing') && Firm::query()->count() === 1) {
            $firmId = (int) Firm::query()->orderBy('id')->value('id');
        }

        if ($firmId === null) {
            return response()->json(['error' => 'FIRM_REQUIRED'], 422);
        }

        return app(FirmContext::class)->run($firmId, $callback);
    }
}

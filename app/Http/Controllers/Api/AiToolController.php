<?php

namespace App\Http\Controllers\Api;

use App\Domain\AI\AiReceptionistService;
use App\Domain\AI\AiToolDefinitions;
use App\Domain\AI\AiToolRegistry;
use App\Domain\Calls\CallService;
use App\Http\Controllers\Controller;
use App\Models\AiSession;
use App\Models\Call;
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
        return response()->json([
            'system_prompt' => $this->receptionist->systemPrompt(),
            'session_context' => $this->receptionist->sessionContext(),
            'tools' => $this->tools->toolNames(),
            'realtime_tools' => AiToolDefinitions::realtimeTools(),
            'realtime_model' => config('services.openai.realtime_model'),
        ]);
    }

    public function invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tool' => ['required', 'string'],
            'arguments' => ['nullable', 'array'],
            'ai_session_id' => ['nullable', 'exists:ai_sessions,id'],
            'provider_call_id' => ['nullable', 'string'],
        ]);

        $session = null;
        if (! empty($data['ai_session_id'])) {
            $session = AiSession::query()->find($data['ai_session_id']);
        } elseif (! empty($data['provider_call_id'])) {
            $call = Call::query()->where('provider_call_id', $data['provider_call_id'])->first();
            $session = $call?->aiSession;
        }

        $result = $this->tools->invoke($data['tool'], $data['arguments'] ?? [], $session);

        return response()->json(['data' => $result]);
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

        $call = Call::query()->where('provider_call_id', $data['provider_call_id'])->firstOrFail();

        if (! empty($data['provider_session_id']) && $call->aiSession) {
            $call->aiSession->update([
                'provider_session_id' => $data['provider_session_id'],
            ]);
        }

        $completed = $this->calls->complete($call, $data);

        return response()->json(['data' => $completed->load('aiSession')]);
    }
}

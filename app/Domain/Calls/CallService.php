<?php

namespace App\Domain\Calls;

use App\Jobs\EnrichCallAfterCompleteJob;
use App\Models\AiSession;
use App\Models\Call;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CallService
{
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Call::query()->with(['lead', 'lawyer.user'])->latest('started_at');
        if (! empty($filters['q'])) {
            $query->where('caller_phone', 'like', '%'.$filters['q'].'%');
        }

        return $query->paginate($perPage);
    }

    public function startInbound(string $providerCallId, string $callerPhone): Call
    {
        $existing = Call::query()->where('provider_call_id', $providerCallId)->first();
        if ($existing) {
            return $existing->load('aiSession');
        }

        $call = Call::query()->create([
            'provider_call_id' => $providerCallId,
            'direction' => 'inbound',
            'caller_phone' => $callerPhone,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        AiSession::query()->create([
            'call_id' => $call->id,
            'started_at' => now(),
            'status' => 'active',
            'metadata_json' => [],
        ]);

        return $call->load('aiSession');
    }

    public function complete(Call $call, array $data = []): Call
    {
        $call->fill([
            'ended_at' => $data['ended_at'] ?? now(),
            'duration_seconds' => $data['duration_seconds'] ?? $call->duration_seconds,
            'status' => $data['status'] ?? 'completed',
            'recording_url' => $data['recording_url'] ?? $call->recording_url,
            'transcript' => $data['transcript'] ?? $call->transcript,
            'summary' => $data['summary'] ?? $call->summary,
            'intent' => $data['intent'] ?? $call->intent,
            'outcome' => $data['outcome'] ?? $call->outcome,
        ])->save();

        $call->aiSession?->update([
            'ended_at' => now(),
            'status' => 'completed',
        ]);

        if (filled($call->transcript) && blank($call->summary)) {
            EnrichCallAfterCompleteJob::dispatch($call->id);
        }

        return $call->fresh();
    }
}

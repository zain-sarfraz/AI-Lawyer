<?php

namespace App\Jobs;

use App\Domain\Firm\FirmContext;
use App\Domain\Integrations\OpenAiService;
use App\Models\Call;
use App\Models\Scopes\FirmScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichCallAfterCompleteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $callId) {}

    public function handle(OpenAiService $openAi): void
    {
        $call = Call::query()->withoutGlobalScope(FirmScope::class)->find($this->callId);
        if (! $call || blank($call->transcript) || filled($call->summary)) {
            return;
        }

        app(FirmContext::class)->run((int) $call->firm_id, function () use ($call, $openAi): void {
            $summary = $openAi->summarizeTranscript($call->transcript);
            if ($summary) {
                $call->update(['summary' => $summary]);
            }
        });
    }
}

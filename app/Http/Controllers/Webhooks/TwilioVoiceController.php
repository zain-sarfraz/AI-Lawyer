<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\AI\AiReceptionistService;
use App\Domain\Calls\CallService;
use App\Domain\Firm\FirmContext;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Firm;
use App\Models\Scopes\FirmScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioVoiceController extends Controller
{
    public function __construct(
        private readonly CallService $calls,
        private readonly AiReceptionistService $receptionist,
    ) {}

    public function incoming(Request $request): Response
    {
        $this->bindInboundFirm($request);

        $callSid = (string) $request->input('CallSid', 'local-'.uniqid());
        $from = (string) $request->input('From', 'unknown');

        $call = $this->calls->startInbound($callSid, $from);
        $session = $call->aiSession;
        $context = $this->receptionist->sessionContext();

        Log::info('Inbound Twilio call received', [
            'call_id' => $call->id,
            'ai_session_id' => $session?->id,
            'provider_call_id' => $callSid,
        ]);

        $wsUrl = rtrim((string) config('services.voice_gateway.public_ws_url'), '/');
        if ($wsUrl === '') {
            // Fallback: speak greeting only when gateway is not configured.
            $say = e($context['greeting'] ?: ('Thank you for calling '.$context['firm_name']));
            $twiml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say voice="Polly.Joanna">{$say}</Say>
  <Say voice="Polly.Joanna">Our virtual receptionist is temporarily unavailable. Please leave a message after the tone or call back during business hours.</Say>
  <Hangup/>
</Response>
XML;

            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }

        $streamUrl = $wsUrl.'/media';
        $statusCallback = url('/api/webhooks/twilio/voice/status');

        // Custom parameters are available to the voice gateway on Twilio "start" event.
        $twiml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Connect>
    <Stream url="{$streamUrl}">
      <Parameter name="call_id" value="{$call->id}" />
      <Parameter name="ai_session_id" value="{$session?->id}" />
      <Parameter name="caller_phone" value="{$from}" />
      <Parameter name="provider_call_id" value="{$callSid}" />
    </Stream>
  </Connect>
  <Redirect method="POST">{$statusCallback}</Redirect>
</Response>
XML;

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function status(Request $request): Response
    {
        $this->withCall((string) $request->input('CallSid'), function (Call $call) use ($request): void {
            $this->calls->complete($call, [
                'status' => strtolower((string) $request->input('CallStatus', 'completed')),
                'duration_seconds' => $request->integer('CallDuration') ?: null,
            ]);
        });

        return response('OK');
    }

    public function recording(Request $request): Response
    {
        $this->withCall((string) $request->input('CallSid'), function (Call $call) use ($request): void {
            $call->update(['recording_url' => $request->input('RecordingUrl')]);
        });

        return response('OK');
    }

    private function bindInboundFirm(Request $request): void
    {
        $firms = app(FirmContext::class);
        if ($firms->id() !== null) {
            return;
        }

        $header = $request->header('X-Firm-Id');
        if (is_numeric($header)) {
            $firms->set((int) $header);

            return;
        }

        $to = (string) $request->input('To', '');
        $firmId = $to !== ''
            ? Firm::query()->where('twilio_phone_number', $to)->value('id')
            : null;
        $firmId ??= Firm::query()->orderBy('id')->value('id');
        if ($firmId !== null) {
            $firms->set((int) $firmId);
        }
    }

    private function withCall(string $providerCallId, callable $callback): void
    {
        $call = Call::query()->withoutGlobalScope(FirmScope::class)
            ->where('provider_call_id', $providerCallId)
            ->first();
        if (! $call) {
            return;
        }

        app(FirmContext::class)->run((int) $call->firm_id, fn () => $callback($call));
    }
}

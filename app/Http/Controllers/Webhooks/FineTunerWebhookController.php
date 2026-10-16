<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Domain\Calls\CallService;
use App\Domain\Firm\FirmContext;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Firm;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\Scopes\FirmScope;
use App\Support\CallTranscript;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FineTunerWebhookController extends Controller
{
    public function __construct(private readonly CallService $calls) {}

    /**
     * Inbound webhook: FineTuner/Synthflow asks how to handle the call.
     * We create the Call row and return metadata for the agent.
     */
    public function inbound(Request $request): JsonResponse
    {
        $payload = $request->all();
        $firm = $this->resolveFirm($payload);
        if (! $firm) {
            Log::warning('FineTuner inbound webhook: firm not resolved', ['payload' => $payload]);

            return response()->json(['error' => 'Firm not found'], 404);
        }

        return app(FirmContext::class)->run((int) $firm->id, function () use ($payload, $firm) {
            $callId = (string) (
                data_get($payload, 'call.call_id')
                ?? data_get($payload, 'call_id')
                ?? data_get($payload, 'call_inbound.call_id')
                ?? uniqid('ft_', true)
            );
            $phone = (string) (
                data_get($payload, 'lead.phone_number')
                ?? data_get($payload, 'from')
                ?? data_get($payload, 'from_number')
                ?? data_get($payload, 'caller_phone')
                ?? data_get($payload, 'call_inbound.from_number')
                ?? data_get($payload, 'call_inbound.from')
                ?? 'unknown'
            );

            $call = $this->calls->startInbound($callId, $phone);

            return response()->json([
                'call_inbound' => [
                    'model_id' => data_get($this->integrationSettings($firm), 'model_id'),
                    'metadata' => [
                        'firm_id' => $firm->id,
                        'laravel_call_id' => $call->id,
                        'provider_call_id' => $call->provider_call_id,
                    ],
                    'prompt_variables' => [
                        'firm_name' => $firm->firm_name,
                        'receptionist_name' => $firm->receptionist_name ?: 'Alex',
                    ],
                ],
            ]);
        });
    }

    /**
     * Post-call webhook: store transcript + complete the call for admin UI.
     */
    public function postCall(Request $request): JsonResponse
    {
        $payload = $request->all();
        $firm = $this->resolveFirm($payload);
        if (! $firm) {
            Log::warning('FineTuner post-call webhook: firm not resolved', ['payload' => $payload]);

            return response()->json(['error' => 'Firm not found'], 404);
        }

        return app(FirmContext::class)->run((int) $firm->id, function () use ($payload) {
            $callId = (string) (
                data_get($payload, 'call.call_id')
                ?? data_get($payload, 'call_id')
                ?? ''
            );
            if ($callId === '') {
                return response()->json(['error' => 'Missing call_id'], 422);
            }

            $phone = (string) (
                data_get($payload, 'lead.phone_number')
                ?? data_get($payload, 'from')
                ?? data_get($payload, 'from_number')
                ?? data_get($payload, 'caller_phone')
                ?? 'unknown'
            );

            $call = Call::query()->withoutGlobalScope(FirmScope::class)
                ->where('provider_call_id', $callId)
                ->first();

            if (! $call) {
                $call = $this->calls->startInbound($callId, $phone);
            }

            $transcript = CallTranscript::normalize(
                (string) (data_get($payload, 'call.transcript') ?? data_get($payload, 'transcript') ?? '')
            );

            $duration = data_get($payload, 'call.duration') ?? data_get($payload, 'duration');
            $recording = data_get($payload, 'call.recording_url') ?? data_get($payload, 'recording_url');
            $status = (string) (data_get($payload, 'call.status') ?? data_get($payload, 'status') ?? 'completed');

            $completed = $this->calls->complete($call, [
                'transcript' => $transcript !== '' ? $transcript : $call->transcript,
                'duration_seconds' => is_numeric($duration) ? (int) $duration : $call->duration_seconds,
                'recording_url' => is_string($recording) && $recording !== '' && ! str_contains($recording, 'REDACTED')
                    ? $recording
                    : $call->recording_url,
                'status' => $status === 'completed' ? 'completed' : $status,
                'outcome' => data_get($payload, 'call.end_call_reason') ?: $call->outcome,
                'intent' => filled($transcript) ? 'phone_ai' : $call->intent,
            ]);

            // Link appointment booked during this call or by matching lead/time
            $appointment = Appointment::query()->withoutGlobalScope(FirmScope::class)
                ->where('call_id', $completed->id)
                ->latest('id')
                ->first();

            if (! $appointment) {
                // Find most recent AI booked appointment within 20 mins for this firm
                $appointment = Appointment::query()->withoutGlobalScope(FirmScope::class)
                    ->where('firm_id', $completed->firm_id)
                    ->whereNull('call_id')
                    ->where('source', 'phone_ai')
                    ->where('created_at', '>=', now()->subMinutes(20))
                    ->latest('id')
                    ->first();

                if ($appointment) {
                    $appointment->update(['call_id' => $completed->id]);
                }
            }

            if ($appointment && $appointment->lead_id && empty($completed->lead_id)) {
                $completed->update(['lead_id' => $appointment->lead_id]);
            }

            if (empty($completed->lead_id) && $phone !== 'unknown') {
                $digits = substr((string) preg_replace('/\D/', '', $phone), -9);
                $matchedLead = Lead::query()
                    ->where('phone', $phone)
                    ->when(strlen($digits) >= 7, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))
                    ->latest('id')
                    ->first();

                if ($matchedLead) {
                    $completed->update(['lead_id' => $matchedLead->id]);
                }
            }

            return response()->json([
                'ok' => true,
                'call_id' => $completed->id,
                'provider_call_id' => $completed->provider_call_id,
            ]);
        });
    }

    private function resolveFirm(array $payload): ?Firm
    {
        $headerFirm = request()->header('X-Firm-Id');
        if (is_numeric($headerFirm)) {
            return Firm::query()->find((int) $headerFirm);
        }

        $directFirmId = data_get($payload, 'firm_id')
            ?? data_get($payload, 'metadata.firm_id')
            ?? data_get($payload, 'call_inbound.metadata.firm_id')
            ?? data_get($payload, 'call.metadata.firm_id');
        if (is_numeric($directFirmId)) {
            $firm = Firm::query()->find((int) $directFirmId);
            if ($firm) {
                return $firm;
            }
        }

        $modelId = (string) (
            data_get($payload, 'call.model_id')
            ?? data_get($payload, 'model_id')
            ?? data_get($payload, 'call_inbound.model_id')
            ?? config('services.synthflow.agent_id')
            ?? ''
        );

        if ($modelId !== '') {
            $integration = Integration::query()
                ->withoutGlobalScopes()
                ->where('provider', 'finetuner')
                ->where('status', 'active')
                ->latest('id')
                ->get()
                ->first(fn (Integration $row) => (string) data_get($row->settings_json, 'model_id') === $modelId);

            if ($integration?->firm_id) {
                return Firm::query()->find((int) $integration->firm_id);
            }
        }

        $to = (string) (
            data_get($payload, 'to')
            ?? data_get($payload, 'to_number')
            ?? data_get($payload, 'phone_number')
            ?? data_get($payload, 'call_inbound.to_number')
            ?? data_get($payload, 'call.phone_number')
            ?? config('services.synthflow.agent_phone')
            ?? ''
        );
        if ($to !== '') {
            $byPhone = Firm::query()->where('twilio_phone_number', $to)->latest('id')->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        if (app()->environment('local', 'testing')) {
            $firmWithServices = Firm::query()
                ->whereHas('services')
                ->orderByDesc('id')
                ->first();

            return $firmWithServices ?: Firm::query()->orderByDesc('id')->first();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function integrationSettings(Firm $firm): array
    {
        $integration = Integration::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('provider', 'finetuner')
            ->first();

        return is_array($integration?->settings_json) ? $integration->settings_json : [];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\AI\AiReceptionistService;
use App\Domain\AI\AiToolDefinitions;
use App\Domain\Firm\FirmService;
use App\Domain\Integrations\FineTunerService;
use App\Domain\Integrations\TelnyxService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiReceptionistController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly FirmService $firm,
        private readonly AiReceptionistService $receptionist,
        private readonly FineTunerService $fineTuner,
        private readonly TelnyxService $telnyx,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ai.settings.manage'),
        ];
    }

    public function show(Request $request): View
    {
        $this->authorize('update', $this->firm->current());

        $today = now()->toDateString();
        $settings = $this->fineTuner->settings();
        $connection = $this->fineTuner->connection();
        $firm = $this->firm->current();
        $online = (bool) (filled(config('services.openai.api_key')) || $this->fineTuner->configured());

        // Skip live voice-gateway ping on page render — it adds latency when gateway is down.
        $gatewayHealth = ['ok' => false, 'skipped' => true];

        $aiStats = Cache::remember("ai.stats.{$firm->id}.{$today}", 30, function () use ($today) {
            return [
                'calls_today' => Call::query()->whereDate('started_at', $today)->count(),
                'leads_today' => Lead::query()->whereDate('created_at', $today)->where('source', 'phone_ai')->count(),
                'bookings_today' => Appointment::query()->whereDate('created_at', $today)->where('source', 'phone_ai')->count(),
                'handoffs_today' => Call::query()->whereDate('started_at', $today)->where('outcome', 'transferred')->count(),
                'avg_duration' => null,
                'active_calls' => Call::query()->where('status', 'in_progress')->count(),
            ];
        });

        return view('admin.ai.show', [
            'firm' => $firm,
            'systemPrompt' => $this->receptionist->systemPrompt(),
            'defaultSystemPrompt' => $this->receptionist->defaultSystemPrompt(),
            'catalogKnowledge' => Cache::remember("ai.catalog.{$firm->id}", 60, fn () => $this->receptionist->catalogKnowledge()),
            'sessionContext' => $this->receptionist->sessionContext(),
            'tools' => AiToolDefinitions::realtimeTools(),
            'gatewayHealth' => $gatewayHealth,
            'publicWsUrl' => config('services.voice_gateway.public_ws_url'),
            'online' => $online,
            'aiStats' => $aiStats,
            'recentAiCalls' => Call::query()->latest('started_at')->limit(6)->get(),
            'fineTuner' => $this->fineTuner->snapshot(),
            'fineTunerKey' => $this->fineTuner->maskedKey(),
            'fineTunerWorkspace' => $connection['workspace_id'] ?? ($settings['workspace_id'] ?? config('services.synthflow.workspace_id')),
            'fineTunerSettings' => $settings,
            'fineTunerWebhooks' => $this->fineTuner->webhookUrls(),
            'fineTunerBaseUrl' => $connection['base_url'] ?? ($settings['base_url'] ?? 'https://api.synthflow.ai/v2'),
            'telnyxConfigured' => $this->telnyx->configured(),
            'telnyxSearchResults' => session('telnyx_search', []),
            'assignedPhone' => $settings['phone_number'] ?? $firm->twilio_phone_number,
            'agentId' => $settings['model_id'] ?? config('services.synthflow.agent_id'),
            'lastSyncedAt' => $settings['last_synced_at'] ?? null,
            'serverMode' => 'synthflow_telnyx',
            'preferredAgentName' => (string) config('services.synthflow.agent_name', 'Qubetech AI Lawyer'),
            'preferredAgentPhone' => (string) config('services.synthflow.agent_phone', '+16097809610'),
        ]);
    }

    public function connectFineTuner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'api_key' => ['required', 'string', 'max:500'],
            'workspace_id' => ['required', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $result = $this->fineTuner->connect($data['api_key'], $data['workspace_id'], $data['base_url'] ?? null);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['api_key' => $result['error'] ?? 'Could not connect to Synthflow.'])->withInput();
        }

        if ($result['linked'] ?? false) {
            $message = 'Synthflow connected. Using '.($result['name'] ?? 'your agent');
            if (! empty($result['phone_number'])) {
                $message .= ' ('.$result['phone_number'].')';
            }
            $message .= '.';
            if (! empty($result['error'])) {
                $message .= ' Note: '.$result['error'];
            }

            return back()->with('success', $message);
        }

        return back()->with('success', 'Synthflow connected. '.($result['error'] ?? 'Pick Qubetech AI Lawyer to attach it.'));
    }

    public function createFineTunerAgent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->fineTuner->ensureAgent($data['name'] ?? null);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['agent' => $result['error'] ?? 'Could not create / update Synthflow agent.']);
        }

        $message = 'Synthflow agent updated'.(isset($result['model_id']) ? ' ('.$result['model_id'].')' : '').'.';
        if (! empty($result['error'])) {
            $message .= ' Note: '.$result['error'];
        }

        return back()->with('success', $message);
    }

    public function assignFineTuner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'model_id' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:40'],
        ]);

        $result = $this->fineTuner->assignNumber($data['model_id'], $data['phone_number']);
        if (! $result['ok']) {
            return back()->withErrors(['phone_number' => $result['error'] ?? 'Could not assign the number.']);
        }

        $message = 'Phone number attached on Synthflow. Greeting, prompt, booking tools, and webhooks were synced.';
        if (! empty($result['error'])) {
            $message .= ' Note: '.$result['error'];
        }

        return back()->with('success', $message);
    }

    public function syncFineTuner(): RedirectResponse
    {
        $result = $this->fineTuner->syncAgent();
        if (! $result['ok']) {
            return back()->withErrors(['sync' => $result['error'] ?? 'Could not sync Synthflow agent.']);
        }

        $message = 'Services, lawyers, greeting, prompt, and booking tools synced to Synthflow.';
        if (! empty($result['error'])) {
            $message .= ' Warning: '.$result['error'];
        }

        return back()->with('success', $message);
    }

    public function searchTelnyx(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $result = $this->telnyx->searchAvailable(
            $data['area_code'] ?? null,
            strtoupper($data['country'] ?? (string) config('services.telnyx.default_country', 'US')),
        );

        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['telnyx' => $result['error'] ?? 'Telnyx search failed.']);
        }

        return back()
            ->with('telnyx_search', $result['numbers'] ?? [])
            ->with('success', count($result['numbers'] ?? []).' Telnyx number(s) found.');
    }

    public function assignTelnyx(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:40'],
            'purchase' => ['sometimes', 'boolean'],
            'model_id' => ['nullable', 'string', 'max:255'],
        ]);

        $number = $this->telnyx->normalizeE164($data['phone_number']);
        if ($number === null) {
            return back()->withErrors(['phone_number' => 'Use E.164 format, e.g. +12202650290.']);
        }

        if ($request->boolean('purchase')) {
            if (! $this->telnyx->configured()) {
                return back()->withErrors(['telnyx' => 'TELNYX_API_KEY is required to purchase numbers.']);
            }
            $order = $this->telnyx->orderNumber($number);
            if (! ($order['ok'] ?? false)) {
                return back()->withErrors(['telnyx' => $order['error'] ?? 'Could not purchase Telnyx number.']);
            }
        }

        $modelId = $data['model_id'] ?? ($this->fineTuner->settings()['model_id'] ?? null);
        if (! filled($modelId)) {
            $created = $this->fineTuner->ensureAgent();
            if (! ($created['ok'] ?? false)) {
                $this->firm->update(['twilio_phone_number' => $number]);
                $this->fineTuner->mergeSettings(['phone_number' => $number]);

                return back()->with('success', 'Number saved on firm. Create / connect Synthflow agent, then attach.')
                    ->withErrors(['agent' => $created['error'] ?? 'Agent not ready yet.']);
            }
            $modelId = $created['model_id'] ?? null;
        }

        $assign = $this->fineTuner->assignNumber((string) $modelId, $number);
        if (! ($assign['ok'] ?? false)) {
            $this->firm->update(['twilio_phone_number' => $number]);
            $this->fineTuner->mergeSettings(['phone_number' => $number]);

            return back()->withErrors(['phone_number' => $assign['error'] ?? 'Number saved, but Synthflow attach failed.']);
        }

        return back()->with('success', 'Telnyx number '.$number.' attached to Synthflow agent.');
    }

    public function testCall(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $phone = $this->telnyx->normalizeE164($data['phone']) ?? $data['phone'];
        $result = $this->fineTuner->placeTestCall($phone, $data['name'] ?? 'Test caller');
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['test_call' => $result['error'] ?? 'Test call failed.'])->withInput();
        }

        return back()->with('success', 'Test call placed to '.$phone.'. Answer your phone to try the booking flow.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'receptionist_name' => ['required', 'string', 'max:255'],
            'ai_greeting' => ['required', 'string', 'max:2000'],
            'ai_tone' => ['required', 'string', 'max:255'],
            'ai_language' => ['required', 'string', 'max:16'],
            'ai_voice' => ['nullable', 'string', 'max:255'],
            'ai_allowed_topics' => ['nullable', 'string', 'max:5000'],
            'ai_escalation_rules' => ['nullable', 'string', 'max:5000'],
            'ai_system_prompt' => ['nullable', 'string', 'max:50000'],
            'transfer_number' => ['nullable', 'string', 'max:50'],
            'after_hours_behavior' => ['required', 'string', 'max:64'],
            'recording_enabled' => ['sometimes', 'boolean'],
            'sync_now' => ['sometimes', 'boolean'],
        ]);

        $syncNow = $request->boolean('sync_now');
        $prompt = trim((string) ($data['ai_system_prompt'] ?? ''));
        unset($data['sync_now'], $data['ai_system_prompt']);

        $data['recording_enabled'] = $request->boolean('recording_enabled');
        $this->firm->update($data);

        $defaultPrompt = $this->receptionist->defaultSystemPrompt();
        $this->firm->update([
            'ai_system_prompt' => ($prompt === '' || $prompt === trim($defaultPrompt)) ? null : $prompt,
        ]);

        $message = 'AI receptionist settings saved.';
        if ($syncNow && filled($this->fineTuner->settings()['model_id'] ?? null)) {
            $sync = $this->fineTuner->syncAgent();
            $message .= $sync['ok']
                ? ' Synthflow agent synced (services & lawyers included).'
                : ' Synthflow sync failed: '.($sync['error'] ?? 'unknown error');
        }

        return back()->with('success', $message);
    }
}

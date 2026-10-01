<?php

namespace App\Http\Controllers\Admin;

use App\Domain\AI\AiReceptionistService;
use App\Domain\AI\AiToolDefinitions;
use App\Domain\Firm\FirmService;
use App\Domain\Integrations\FineTunerService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AiReceptionistController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly FirmService $firm,
        private readonly AiReceptionistService $receptionist,
        private readonly FineTunerService $fineTuner,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ai.settings.manage'),
        ];
    }

    public function show(): View
    {
        $this->authorize('update', $this->firm->current());

        $gatewayHealth = null;
        $gatewayUrl = rtrim((string) config('services.voice_gateway.url'), '/');
        if ($gatewayUrl !== '') {
            try {
                $gatewayHealth = Http::timeout(2)->get($gatewayUrl.'/health')->json();
            } catch (\Throwable) {
                $gatewayHealth = ['ok' => false, 'error' => 'unreachable'];
            }
        }

        $today = now()->toDateString();
        $online = (bool) (($gatewayHealth['ok'] ?? false) || filled(config('services.openai.api_key')));

        $avgDuration = Call::query()
            ->whereDate('started_at', $today)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');

        return view('admin.ai.show', [
            'firm' => $this->firm->current(),
            'systemPrompt' => $this->receptionist->systemPrompt(),
            'sessionContext' => $this->receptionist->sessionContext(),
            'tools' => AiToolDefinitions::realtimeTools(),
            'gatewayHealth' => $gatewayHealth,
            'publicWsUrl' => config('services.voice_gateway.public_ws_url'),
            'online' => $online,
            'aiStats' => [
                'calls_today' => Call::query()->whereDate('started_at', $today)->count(),
                'leads_today' => Lead::query()->whereDate('created_at', $today)->where('source', 'phone_ai')->count(),
                'bookings_today' => Appointment::query()->whereDate('created_at', $today)->where('source', 'phone_ai')->count(),
                'handoffs_today' => Call::query()->whereDate('started_at', $today)->where('outcome', 'transferred')->count(),
                'avg_duration' => $avgDuration ? (int) round($avgDuration) : null,
                'active_calls' => Call::query()->where('status', 'in_progress')->count(),
            ],
            'recentAiCalls' => Call::query()->latest('started_at')->limit(6)->get(),
            'fineTuner' => $this->fineTuner->snapshot(),
            'fineTunerKey' => $this->fineTuner->maskedKey(),
            'fineTunerWorkspace' => $this->fineTuner->connection()['workspace_id'] ?? null,
        ]);
    }

    public function connectFineTuner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'api_key' => ['required', 'string', 'max:500'],
            'workspace_id' => ['required', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $this->fineTuner->connect($data['api_key'], $data['workspace_id'], $data['base_url'] ?? null);
        $snapshot = $this->fineTuner->snapshot();

        if ($snapshot['error']) {
            return back()->withErrors(['api_key' => $snapshot['error']]);
        }

        return back()->with('success', 'FineTuner connected. Phone numbers and agents are ready.');
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

        return back()->with('success', 'Phone number attached to the FineTuner agent.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'receptionist_name' => ['nullable', 'string', 'max:255'],
            'ai_greeting' => ['nullable', 'string'],
            'ai_tone' => ['nullable', 'string', 'max:255'],
            'ai_language' => ['nullable', 'string', 'max:16'],
            'ai_escalation_rules' => ['nullable', 'string'],
            'transfer_number' => ['nullable', 'string', 'max:50'],
            'after_hours_behavior' => ['required', 'string', 'max:64'],
        ]);

        $this->firm->update($data);

        return back()->with('success', 'AI receptionist settings updated.');
    }
}

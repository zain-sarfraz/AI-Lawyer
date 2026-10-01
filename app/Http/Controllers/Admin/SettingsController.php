<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Firm\FirmService;
use App\Domain\Integrations\IntegrationCredentialService;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly FirmService $firm,
        private readonly IntegrationCredentialService $integrations,
    ) {}

    public function edit(): View
    {
        return view('settings.edit', [
            'firm' => $this->firm->current(),
            'integrations' => Integration::query()->orderBy('provider')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'max:8'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'string', 'max:255'],
            'receptionist_name' => ['nullable', 'string', 'max:255'],
            'ai_greeting' => ['nullable', 'string'],
            'ai_tone' => ['nullable', 'string', 'max:255'],
            'ai_language' => ['nullable', 'string', 'max:16'],
            'slot_interval_minutes' => ['required', 'integer', 'min:5'],
            'default_duration_minutes' => ['required', 'integer', 'min:5'],
            'buffer_minutes' => ['required', 'integer', 'min:0'],
            'booking_horizon_days' => ['required', 'integer', 'min:1'],
            'cancellation_window_hours' => ['required', 'integer', 'min:0'],
            'transfer_number' => ['nullable', 'string', 'max:50'],
            'after_hours_behavior' => ['required', 'string', 'max:64'],
            'recording_enabled' => ['sometimes', 'boolean'],
        ]) + ['recording_enabled' => $request->boolean('recording_enabled')];

        $this->firm->update($data);

        return back()->with('success', 'Settings saved.');
    }

    public function updateIntegration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:64'],
            'api_key' => ['nullable', 'string'],
            'account_sid' => ['nullable', 'string'],
            'auth_token' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $credentials = array_filter([
            'api_key' => $data['api_key'] ?? null,
            'account_sid' => $data['account_sid'] ?? null,
            'auth_token' => $data['auth_token'] ?? null,
        ]);

        $this->integrations->upsert($data['provider'], $credentials, [], $data['status']);

        return back()->with('success', 'Integration saved. Credentials are encrypted and will not be shown again in full.');
    }

    public function audit(): View
    {
        return view('settings.audit', [
            'logs' => AuditLog::query()->with('user')->latest('created_at')->paginate(40),
        ]);
    }
}

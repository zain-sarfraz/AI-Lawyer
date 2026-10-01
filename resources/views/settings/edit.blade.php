@extends('layouts.app')

@section('content')
<x-legal.page-header title="Settings" subtitle="Firm profile, AI persona, scheduling, and encrypted integrations" />

<nav class="legal-settings-nav">
    <a href="#general" class="is-active">General</a>
    <a href="#ai">AI Receptionist</a>
    <a href="#scheduling">Scheduling</a>
    <a href="#calls">Call settings</a>
    <a href="#integrations">Integrations</a>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <x-legal.card>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <div class="legal-form-section" id="general">
                    <h3>Firm information</h3>
                    <p>Identity and contact details shown across the workspace.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ui-label">Firm name</label>
                            <input name="firm_name" class="ui-field" value="{{ old('firm_name', $firm->firm_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Legal name</label>
                            <input name="legal_name" class="ui-field" value="{{ old('legal_name', $firm->legal_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Phone</label>
                            <input name="phone" class="ui-field" value="{{ old('phone', $firm->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Email</label>
                            <input name="email" class="ui-field" value="{{ old('email', $firm->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Website</label>
                            <input name="website" class="ui-field" value="{{ old('website', $firm->website) }}">
                        </div>
                        <div class="col-12">
                            <label class="ui-label">Address</label>
                            <textarea name="address" class="ui-field" rows="2">{{ old('address', $firm->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Timezone</label>
                            <input name="timezone" class="ui-field" list="firm-timezones" value="{{ old('timezone', $firm->timezone) }}" required>
                            <datalist id="firm-timezones">
                                <option value="Asia/Karachi"></option>
                                <option value="America/New_York"></option>
                                <option value="Europe/London"></option>
                                <option value="UTC"></option>
                            </datalist>
                            <p class="mt-1 mb-0">Shown on the desk. New firms start in Asia/Karachi. Stored appointment times stay UTC.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Currency</label>
                            <input name="currency" class="ui-field" value="{{ old('currency', $firm->currency) }}" required>
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="ai">
                    <h3>AI receptionist</h3>
                    <p>Persona, greeting, tone, and escalation defaults.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ui-label">Receptionist name</label>
                            <input name="receptionist_name" class="ui-field" value="{{ old('receptionist_name', $firm->receptionist_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">AI tone</label>
                            <input name="ai_tone" class="ui-field" value="{{ old('ai_tone', $firm->ai_tone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">AI language</label>
                            <input name="ai_language" class="ui-field" value="{{ old('ai_language', $firm->ai_language) }}">
                        </div>
                        <div class="col-12">
                            <label class="ui-label">Greeting</label>
                            <textarea name="ai_greeting" class="ui-field" rows="2">{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Transfer number</label>
                            <input name="transfer_number" class="ui-field" value="{{ old('transfer_number', $firm->transfer_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">After-hours behavior</label>
                            <input name="after_hours_behavior" class="ui-field" value="{{ old('after_hours_behavior', $firm->after_hours_behavior) }}">
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="scheduling">
                    <h3>Scheduling</h3>
                    <p>Default slot rules used by staff booking and AI tools.</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="ui-label">Slot interval</label>
                            <input type="number" name="slot_interval_minutes" class="ui-field" value="{{ old('slot_interval_minutes', $firm->slot_interval_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Default duration</label>
                            <input type="number" name="default_duration_minutes" class="ui-field" value="{{ old('default_duration_minutes', $firm->default_duration_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Buffer</label>
                            <input type="number" name="buffer_minutes" class="ui-field" value="{{ old('buffer_minutes', $firm->buffer_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Horizon (days)</label>
                            <input type="number" name="booking_horizon_days" class="ui-field" value="{{ old('booking_horizon_days', $firm->booking_horizon_days) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Cancellation window (hours)</label>
                            <input type="number" name="cancellation_window_hours" class="ui-field" value="{{ old('cancellation_window_hours', $firm->cancellation_window_hours) }}">
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="calls">
                    <h3>Call settings</h3>
                    <p>Recording and related voice policies.</p>
                    <div class="ui-check">
                        <input type="checkbox" class="ui-check-input" name="recording_enabled" value="1" id="recording_enabled" @checked(old('recording_enabled', $firm->recording_enabled))>
                        <label class="ui-check-label" for="recording_enabled">Enable call recording</label>
                    </div>
                </div>

                <button class="ui-btn ui-btn-primary">Save settings</button>
            </form>
        </x-legal.card>
    </div>

    <div class="col-lg-4">
        <x-legal.card title="Integrations" id="integrations">
            <p class="small ui-muted mb-3">Credentials are encrypted at rest and never re-displayed in full after save.</p>
            <form method="POST" action="{{ route('settings.integrations.update') }}" class="mb-3">
                @csrf
                <label class="ui-label">Provider</label>
                <select name="provider" class="ui-field mb-2">
                    <option value="openai">OpenAI</option>
                    <option value="twilio">Twilio</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </select>
                <input name="api_key" class="ui-field mb-2" placeholder="API key / auth token">
                <input name="account_sid" class="ui-field mb-2" placeholder="Account SID (Twilio)">
                <input name="auth_token" class="ui-field mb-2" placeholder="Auth token">
                <select name="status" class="ui-field mb-2">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="ui-btn ui-btn-ghost w-100">Save integration</button>
            </form>

            <ul class="list-unstyled small mb-0">
                @forelse($integrations as $integration)
                    <li class="legal-list-item">
                        <div>
                            <div class="fw-semibold">{{ strtoupper($integration->provider) }}</div>
                            <div class="ui-muted">{{ collect($integration->maskedCredentials())->map(fn ($v, $k) => "$k=$v")->join(', ') }}</div>
                        </div>
                        <x-legal.badge :tone="$integration->status === 'active' ? 'success' : 'soft'">
                            {{ $integration->status }}
                        </x-legal.badge>
                    </li>
                @empty
                    <li class="ui-muted">No integrations configured.</li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

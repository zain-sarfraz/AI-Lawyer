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
                            <label class="form-label">Firm name</label>
                            <input name="firm_name" class="form-control" value="{{ old('firm_name', $firm->firm_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Legal name</label>
                            <input name="legal_name" class="form-control" value="{{ old('legal_name', $firm->legal_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input name="phone" class="form-control" value="{{ old('phone', $firm->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input name="email" class="form-control" value="{{ old('email', $firm->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Website</label>
                            <input name="website" class="form-control" value="{{ old('website', $firm->website) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $firm->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <input name="timezone" class="form-control" value="{{ old('timezone', $firm->timezone) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <input name="currency" class="form-control" value="{{ old('currency', $firm->currency) }}" required>
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="ai">
                    <h3>AI receptionist</h3>
                    <p>Persona, greeting, tone, and escalation defaults.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Receptionist name</label>
                            <input name="receptionist_name" class="form-control" value="{{ old('receptionist_name', $firm->receptionist_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">AI tone</label>
                            <input name="ai_tone" class="form-control" value="{{ old('ai_tone', $firm->ai_tone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">AI language</label>
                            <input name="ai_language" class="form-control" value="{{ old('ai_language', $firm->ai_language) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Greeting</label>
                            <textarea name="ai_greeting" class="form-control" rows="2">{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer number</label>
                            <input name="transfer_number" class="form-control" value="{{ old('transfer_number', $firm->transfer_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">After-hours behavior</label>
                            <input name="after_hours_behavior" class="form-control" value="{{ old('after_hours_behavior', $firm->after_hours_behavior) }}">
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="scheduling">
                    <h3>Scheduling</h3>
                    <p>Default slot rules used by staff booking and AI tools.</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Slot interval</label>
                            <input type="number" name="slot_interval_minutes" class="form-control" value="{{ old('slot_interval_minutes', $firm->slot_interval_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Default duration</label>
                            <input type="number" name="default_duration_minutes" class="form-control" value="{{ old('default_duration_minutes', $firm->default_duration_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Buffer</label>
                            <input type="number" name="buffer_minutes" class="form-control" value="{{ old('buffer_minutes', $firm->buffer_minutes) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horizon (days)</label>
                            <input type="number" name="booking_horizon_days" class="form-control" value="{{ old('booking_horizon_days', $firm->booking_horizon_days) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cancellation window (hours)</label>
                            <input type="number" name="cancellation_window_hours" class="form-control" value="{{ old('cancellation_window_hours', $firm->cancellation_window_hours) }}">
                        </div>
                    </div>
                </div>

                <div class="legal-form-section" id="calls">
                    <h3>Call settings</h3>
                    <p>Recording and related voice policies.</p>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="recording_enabled" value="1" id="recording_enabled" @checked(old('recording_enabled', $firm->recording_enabled))>
                        <label class="form-check-label" for="recording_enabled">Enable call recording</label>
                    </div>
                </div>

                <button class="btn btn-legal-primary">Save settings</button>
            </form>
        </x-legal.card>
    </div>

    <div class="col-lg-4">
        <x-legal.card title="Integrations" id="integrations">
            <p class="small text-muted mb-3">Credentials are encrypted at rest and never re-displayed in full after save.</p>
            <form method="POST" action="{{ route('settings.integrations.update') }}" class="mb-3">
                @csrf
                <label class="form-label">Provider</label>
                <select name="provider" class="form-select mb-2">
                    <option value="openai">OpenAI</option>
                    <option value="twilio">Twilio</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </select>
                <input name="api_key" class="form-control mb-2" placeholder="API key / auth token">
                <input name="account_sid" class="form-control mb-2" placeholder="Account SID (Twilio)">
                <input name="auth_token" class="form-control mb-2" placeholder="Auth token">
                <select name="status" class="form-select mb-2">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="btn btn-legal-secondary w-100">Save integration</button>
            </form>

            <ul class="list-unstyled small mb-0">
                @forelse($integrations as $integration)
                    <li class="legal-list-item">
                        <div>
                            <div class="fw-semibold">{{ strtoupper($integration->provider) }}</div>
                            <div class="text-muted">{{ collect($integration->maskedCredentials())->map(fn ($v, $k) => "$k=$v")->join(', ') }}</div>
                        </div>
                        <x-legal.badge :tone="$integration->status === 'active' ? 'success' : 'soft'">
                            {{ $integration->status }}
                        </x-legal.badge>
                    </li>
                @empty
                    <li class="text-muted">No integrations configured.</li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

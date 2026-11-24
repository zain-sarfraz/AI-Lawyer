@extends('layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $section = request('section', 'general');
@endphp

<x-legal.page-header
    title="Settings"
    subtitle="Firm profile, AI persona, scheduling, and encrypted integrations"
/>

<nav class="legal-settings-nav" aria-label="Settings sections">
    <a href="#general" class="{{ $section === 'general' ? 'is-active' : '' }}">Firm profile</a>
    <a href="#ai">AI receptionist</a>
    <a href="#scheduling">Scheduling</a>
    <a href="#calls">Call policies</a>
    <a href="#integrations">Integrations</a>
</nav>

<div class="settings-layout">
    <div class="settings-main">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-legal.card>
                <div class="legal-form-section" id="general">
                    <h3>Firm profile</h3>
                    <p>Identity and contact details used across the desk, booking tools, and AI prompts.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ui-label">Firm name</label>
                            <input name="firm_name" class="ui-field" value="{{ old('firm_name', $firm->firm_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Legal name</label>
                            <input name="legal_name" class="ui-field" value="{{ old('legal_name', $firm->legal_name) }}" placeholder="Shown to callers when set">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Phone</label>
                            <input name="phone" class="ui-field" value="{{ old('phone', $firm->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Email</label>
                            <input name="email" type="email" class="ui-field" value="{{ old('email', $firm->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Website</label>
                            <input name="website" class="ui-field" value="{{ old('website', $firm->website) }}" placeholder="https://">
                        </div>
                        <div class="col-12">
                            <label class="ui-label">Address</label>
                            <textarea name="address" class="ui-field" rows="2" placeholder="Office address callers may ask about">{{ old('address', $firm->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Timezone</label>
                            <input name="timezone" class="ui-field" list="firm-timezones" value="{{ old('timezone', $firm->timezone) }}" required>
                            <datalist id="firm-timezones">
                                <option value="Asia/Karachi"></option>
                                <option value="America/New_York"></option>
                                <option value="America/Los_Angeles"></option>
                                <option value="Europe/London"></option>
                                <option value="UTC"></option>
                            </datalist>
                            <p class="mt-1 mb-0 text-xs text-(--muted)">Used for booking windows and AI availability. Appointment storage stays UTC.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Currency</label>
                            <input name="currency" class="ui-field" value="{{ old('currency', $firm->currency) }}" required maxlength="8">
                            <p class="mt-1 mb-0 text-xs text-(--muted)">Default currency for fees and AI pricing answers.</p>
                        </div>
                    </div>
                </div>
            </x-legal.card>

            <x-legal.card>
                <div class="legal-form-section" id="ai">
                    <h3>AI receptionist</h3>
                    <p>Persona, greeting, tone, and escalation defaults injected into the live prompt.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ui-label">Receptionist name</label>
                            <input name="receptionist_name" class="ui-field" value="{{ old('receptionist_name', $firm->receptionist_name) }}" placeholder="Sara">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">AI tone</label>
                            <input name="ai_tone" class="ui-field" value="{{ old('ai_tone', $firm->ai_tone) }}" placeholder="Professional">
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">AI language</label>
                            <input name="ai_language" class="ui-field" value="{{ old('ai_language', $firm->ai_language) }}" placeholder="en">
                        </div>
                        <div class="col-12">
                            <label class="ui-label">Greeting</label>
                            <textarea name="ai_greeting" class="ui-field" rows="3" placeholder="First words callers hear">{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Transfer number</label>
                            <input name="transfer_number" class="ui-field" value="{{ old('transfer_number', $firm->transfer_number) }}" placeholder="Human handoff line">
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">After-hours behavior</label>
                            <select name="after_hours_behavior" class="ui-field">
                                @php $afterHours = old('after_hours_behavior', $firm->after_hours_behavior ?: 'callback'); @endphp
                                <option value="callback" @selected($afterHours === 'callback')>Offer callback</option>
                                <option value="voicemail" @selected($afterHours === 'voicemail')>Take a message</option>
                                <option value="transfer" @selected($afterHours === 'transfer')>Transfer if available</option>
                                <option value="book_only" @selected($afterHours === 'book_only')>Book next available only</option>
                            </select>
                        </div>
                    </div>
                </div>
            </x-legal.card>

            <x-legal.card>
                <div class="legal-form-section" id="scheduling">
                    <h3>Scheduling rules</h3>
                    <p>Slot length, buffers, and booking horizon used by staff booking and AI tools.</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="ui-label">Slot interval (min)</label>
                            <input type="number" min="5" name="slot_interval_minutes" class="ui-field" value="{{ old('slot_interval_minutes', $firm->slot_interval_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Default duration (min)</label>
                            <input type="number" min="5" name="default_duration_minutes" class="ui-field" value="{{ old('default_duration_minutes', $firm->default_duration_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Buffer after (min)</label>
                            <input type="number" min="0" name="buffer_minutes" class="ui-field" value="{{ old('buffer_minutes', $firm->buffer_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="ui-label">Horizon (days)</label>
                            <input type="number" min="1" name="booking_horizon_days" class="ui-field" value="{{ old('booking_horizon_days', $firm->booking_horizon_days) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ui-label">Cancellation window (hours)</label>
                            <input type="number" min="0" name="cancellation_window_hours" class="ui-field" value="{{ old('cancellation_window_hours', $firm->cancellation_window_hours) }}" required>
                        </div>
                    </div>
                </div>
            </x-legal.card>

            <x-legal.card>
                <div class="legal-form-section border-0 pb-0 mb-0" id="calls">
                    <h3>Call policies</h3>
                    <p>Recording and related voice policies for this firm.</p>
                    <div class="ui-check">
                        <input type="checkbox" class="ui-check-input" name="recording_enabled" value="1" id="recording_enabled" @checked(old('recording_enabled', $firm->recording_enabled))>
                        <label class="ui-check-label" for="recording_enabled">Enable call recording</label>
                    </div>
                </div>
            </x-legal.card>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <p class="mb-0 text-xs text-(--muted)">Changes apply to booking + AI catalog immediately. Re-sync AI Receptionist to push the prompt to Synthflow.</p>
                <button type="submit" class="ui-btn ui-btn-primary">Save settings</button>
            </div>
        </form>
    </div>

    <aside class="settings-side" id="integrations">
        <x-legal.card title="Integrations">
            <p class="small ui-muted mb-3">Credentials are encrypted at rest and never re-displayed in full after save.</p>
            <form method="POST" action="{{ route('settings.integrations.update') }}" class="mb-3 space-y-2">
                @csrf
                <label class="ui-label">Provider</label>
                <select name="provider" class="ui-field">
                    <option value="openai">OpenAI</option>
                    <option value="twilio">Twilio</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </select>
                <input name="api_key" class="ui-field" placeholder="API key / auth token">
                <input name="account_sid" class="ui-field" placeholder="Account SID (Twilio)">
                <input name="auth_token" class="ui-field" placeholder="Auth token">
                <select name="status" class="ui-field">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="ui-btn ui-btn-ghost w-full">Save integration</button>
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

        <x-legal.card title="Live usage">
            <ul class="settings-usage-list">
                <li><strong>Timezone</strong> drives booking slots and AI “today”.</li>
                <li><strong>Currency</strong> is used when quoting service fees.</li>
                <li><strong>Greeting / tone / language</strong> feed the receptionist prompt.</li>
                <li><strong>Slot interval</strong> sets consultation length for AI + desk booking.</li>
                <li><strong>Address / phone / website</strong> appear in catalog knowledge.</li>
            </ul>
        </x-legal.card>
    </aside>
</div>
@endsection

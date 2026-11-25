@extends('layouts.app')

@section('content')
@php
    $connected = (bool) ($fineTuner['connected'] ?? false);
    $hasAgent = filled($agentId);
    $promptValue = old('ai_system_prompt', $firm->ai_system_prompt ?? '');
    $phone = $assignedPhone ?: ($fineTunerSettings['phone_number'] ?? null);
@endphp

<div class="agent-page">
    <header class="agent-page-header">
        <div>
            <p class="agent-kicker">{{ $firm->firm_name }}</p>
            <h1>AI Receptionist settings</h1>
            <p class="agent-subtitle">
                Public-facing voice AI — Telnyx number + Synthflow agent kept in sync with your firm services and lawyers.
            </p>
        </div>
        <div class="agent-header-actions">
            @if($hasAgent)
                <form method="POST" action="{{ route('ai.synthflow.sync') }}">
                    @csrf
                    <button class="ui-btn ui-btn-brass">
                        <x-ui.icon name="refresh" class="h-4 w-4" />
                        Sync services &amp; lawyers
                    </button>
                </form>
            @endif
            <button type="submit" form="ai-agent-form" class="ui-btn ui-btn-primary">Update Synthflow agent</button>
        </div>
    </header>

    @if(session('success'))
        <div class="ui-alert ui-alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="ui-alert ui-alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- Telnyx + Synthflow (recommended) --}}
    <section class="agent-card sf-panel">
        <div class="agent-card-head">
            <div>
                <h3>Telnyx + Synthflow <span class="agent-prompt-badge">recommended</span></h3>
                <p>
                    Dedicated Telnyx number for the firm. Synthflow AI answers calls, explains services/fees/lawyers,
                    books appointments, then posts results to our webhook.
                </p>
            </div>
        </div>

        <div class="sf-mode-banner">
            <div>
                <strong>Server mode: {{ $serverMode }}</strong>
                <span> — this firm uses Synthflow{{ $telnyxConfigured ? ' + Telnyx' : '' }}.</span>
            </div>
            <div class="sf-meta-lines">
                <div><span>Telnyx / phone number</span><strong>{{ $phone ?: 'Not assigned' }}</strong></div>
                <div><span>Synthflow agent</span><strong class="mono">{{ $agentId ?: 'Not attached' }}</strong></div>
                <div>
                    <span>Last synced</span>
                    <strong>
                        @if($lastSyncedAt)
                            @php
                                $syncTz = $firm->resolvedTimezone();
                                $syncAt = \Illuminate\Support\Carbon::parse($lastSyncedAt)->timezone($syncTz);
                            @endphp
                            {{ $syncAt->format('d M Y, h:i:s A') }}
                            <span class="text-(--muted) text-xs">({{ $syncTz }})</span>
                        @else
                            Never
                        @endif
                    </strong>
                </div>
            </div>
        </div>

        <div class="sf-step">
            <h4>1. Search &amp; assign a Telnyx number</h4>
            <form method="POST" action="{{ route('ai.telnyx.search') }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-3">
                    <label class="ui-label">Area code (optional)</label>
                    <input name="area_code" class="ui-field" value="{{ old('area_code') }}" placeholder="415">
                </div>
                <div class="col-md-3">
                    <label class="ui-label">Country</label>
                    <input name="country" class="ui-field" value="{{ old('country', config('services.telnyx.default_country', 'US')) }}" maxlength="2">
                </div>
                <div class="col-md-3">
                    <button class="ui-btn ui-btn-primary" @if(! $telnyxConfigured) disabled @endif>Search Telnyx</button>
                </div>
            </form>
            @unless($telnyxConfigured)
                <p class="small ui-muted mb-3">Set <code>TELNYX_API_KEY</code> in <code>.env</code> to enable search/purchase. You can still attach an existing E.164 number below.</p>
            @endunless

            @if(!empty($telnyxSearchResults))
                <div class="sf-number-grid mb-3">
                    @foreach($telnyxSearchResults as $result)
                        <form method="POST" action="{{ route('ai.telnyx.assign') }}" class="sf-number-card">
                            @csrf
                            <input type="hidden" name="phone_number" value="{{ $result['number'] }}">
                            <input type="hidden" name="purchase" value="1">
                            @if($agentId)
                                <input type="hidden" name="model_id" value="{{ $agentId }}">
                            @endif
                            <strong>{{ $result['number'] }}</strong>
                            <span>{{ trim(($result['locality'] ?? '').' '.($result['region'] ?? '')) ?: 'Available' }}</span>
                            <button class="ui-btn ui-btn-ghost">Buy &amp; assign</button>
                        </form>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('ai.telnyx.assign') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="ui-label">Or attach an existing Telnyx number (E.164)</label>
                    <input name="phone_number" class="ui-field" value="{{ old('phone_number', $phone) }}" placeholder="+15551234567" required>
                </div>
                @if($agentId)
                    <input type="hidden" name="model_id" value="{{ $agentId }}">
                @endif
                <div class="col-md-3">
                    <button class="ui-btn ui-btn-primary">Assign existing</button>
                </div>
            </form>
        </div>

        <div class="sf-step">
            <label class="ui-label">Post-call webhook (configured on the agent automatically)</label>
            <code class="sf-webhook">{{ $fineTunerWebhooks['post_call'] }}</code>
            <p class="small ui-muted mt-2 mb-0">
                Requires <code>APP_URL</code> to be a public HTTPS URL Synthflow can reach. After each call, transcript/booking details are posted here.
            </p>
        </div>

        @unless($connected)
            <div class="sf-step border-top pt-3">
                <h4>Connect Synthflow workspace</h4>
                <form method="POST" action="{{ route('ai.synthflow.connect') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="ui-label">API key <span class="req">*</span></label>
                        <input type="password" name="api_key" class="ui-field" placeholder="{{ $fineTunerKey ?: 'Synthflow API key' }}" required autocomplete="off">
                    </div>
                    <div class="col-md-7">
                        <label class="ui-label">Workspace ID <span class="req">*</span></label>
                        <input name="workspace_id" class="ui-field" value="{{ old('workspace_id', $fineTunerWorkspace) }}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="ui-label">Region</label>
                        <select name="base_url" class="ui-field">
                            <option value="https://api.synthflow.ai/v2" @selected(($fineTunerBaseUrl ?? '') === 'https://api.synthflow.ai/v2')>Global</option>
                            <option value="https://api.us.synthflow.ai/v2" @selected(($fineTunerBaseUrl ?? '') === 'https://api.us.synthflow.ai/v2')>United States</option>
                            <option value="https://api.eu.synthflow.ai/v2" @selected(($fineTunerBaseUrl ?? '') === 'https://api.eu.synthflow.ai/v2')>European Union</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="ui-btn ui-btn-primary">Connect Synthflow</button>
                    </div>
                </form>
            </div>
        @endunless
    </section>

    {{-- AI agent (Synthflow) --}}
    <section class="agent-card">
        <div class="agent-card-head">
            <div>
                <h3>AI agent (Synthflow)</h3>
                <p>Voice, language, and greeting. Sync services after you change catalog so callers get accurate fees.</p>
            </div>
            <span class="agent-prompt-badge">{{ filled($promptValue) ? 'Custom prompt' : 'Standard template' }}</span>
        </div>

        <form id="ai-agent-form" method="POST" action="{{ route('ai.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="sync_now" value="1">
            <input type="hidden" name="ai_tone" value="{{ $firm->ai_tone ?: 'Professional' }}">
            <input type="hidden" name="after_hours_behavior" value="{{ $firm->after_hours_behavior ?: 'callback' }}">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ui-label">Receptionist name</label>
                    <input name="receptionist_name" class="ui-field" value="{{ old('receptionist_name', $firm->receptionist_name) }}" placeholder="e.g. Sarah">
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Language</label>
                    <select name="ai_language" class="ui-field" required>
                        <option value="en" @selected(old('ai_language', $firm->ai_language ?: 'en') === 'en')>English</option>
                        <option value="ur" @selected(old('ai_language', $firm->ai_language ?: 'en') === 'ur')>Urdu (اردو)</option>
                    </select>
                    <p class="small ui-muted mt-1 mb-0">
                        English uses your Synthflow agent's own voice. Urdu uses voice <code>{{ \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID }}</code>.
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Voice ID <span class="opt">override</span></label>
                    <input name="ai_voice" class="ui-field" value="{{ old('ai_voice', $firm->ai_voice) }}" placeholder="Use agent default voice (optional override)">
                    <p class="small ui-muted mt-1 mb-0">Leave blank to use the agent's voice for English or <code>{{ \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID }}</code> for Urdu.</p>
                </div>
                <div class="col-md-12 d-flex align-items-end">
                    <label class="ui-check mb-1">
                        <input type="checkbox" class="ui-check-input" name="recording_enabled" value="1" @checked(old('recording_enabled', $firm->recording_enabled))>
                        <span>Call recording enabled</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="ui-label">First message</label>
                    <textarea name="ai_greeting" class="ui-field" rows="3" placeholder="{{ $resolvedGreeting }}">{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                    <p class="small ui-muted mt-1 mb-0">
                        Synced to Synthflow as <strong>agent speaks first</strong> (<code>agent_static</code>). Leave blank to use the default Turn 1 greeting.
                    </p>
                </div>
            </div>

            <div class="agent-save-row">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary">Update Synthflow agent</button>
                    <button type="button" class="ui-btn ui-btn-brass" @if(! $hasAgent) disabled @endif
                        onclick="document.getElementById('sync-only-form').submit()">
                        Sync services &amp; lawyers
                    </button>
                </div>
                <span class="small ui-muted mono">Agent: {{ $agentId ?: '—' }}</span>
            </div>
        </form>
        <form id="sync-only-form" method="POST" action="{{ route('ai.synthflow.sync') }}" class="d-none">@csrf</form>
    </section>

    {{-- Complete prompt preview + test call --}}
    <div class="agent-grid agent-grid-secondary">
        <section class="agent-card">
            <div class="agent-card-head">
                <div>
                    <h3>Complete prompt (sent to Synthflow)</h3>
                    <p>Full multi-tenant prompt combining the static template and your live account data block.</p>
                </div>
            </div>
            <pre class="sf-catalog" style="max-height: 520px; overflow-y: auto;">{{ $completePrompt }}</pre>
        </section>

        <section class="agent-card">
            <div class="agent-card-head">
                <div>
                    <h3>Test call</h3>
                    <p>The Synthflow agent calls this number so you can try services, lawyers, and booking.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('ai.synthflow.test-call') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="ui-label">Your phone (E.164)</label>
                    <input name="phone" class="ui-field" value="{{ old('phone') }}" placeholder="+923001234567" required>
                </div>
                <div class="col-12">
                    <label class="ui-label">Name</label>
                    <input name="name" class="ui-field" value="{{ old('name', 'Test caller') }}">
                </div>
                <div class="col-12">
                    <button class="ui-btn ui-btn-primary" @if(! $hasAgent) disabled @endif>Place test call</button>
                </div>
            </form>
            <div class="sf-meta-lines mt-3">
                <div><span>Synthflow Agent ID</span><strong class="mono">{{ $agentId ?: '—' }}</strong></div>
                <div>
                    <span>Last synced</span>
                    <strong>
                        @if($lastSyncedAt)
                            @php
                                $syncTz = $firm->resolvedTimezone();
                                $syncAt = \Illuminate\Support\Carbon::parse($lastSyncedAt)->timezone($syncTz);
                            @endphp
                            {{ $syncAt->format('d M Y, h:i:s A') }}
                            <span class="text-(--muted) text-xs">({{ $syncTz }})</span>
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div><span>Phone on agent</span><strong>{{ $phone ?: '—' }}</strong></div>
            </div>
        </section>
    </div>

</div>
@endsection

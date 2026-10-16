@extends('layouts.app')

@section('content')
@php
    $connected = (bool) ($fineTuner['connected'] ?? false);
    $hasAgent = filled($agentId);
    $promptValue = old('ai_system_prompt', $firm->ai_system_prompt ?: $defaultSystemPrompt);
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
                <div><span>Synthflow agent</span><strong class="mono">{{ $agentId ?: 'Not created' }}</strong></div>
                <div>
                    <span>Last synced</span>
                    <strong>
                        @if($lastSyncedAt)
                            {{ \Illuminate\Support\Carbon::parse($lastSyncedAt)->timezone($firm->resolvedTimezone())->format('n/j/Y, g:i:s A') }}
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
            <h4>2. Use your Synthflow agent</h4>
            <p class="small ui-muted mb-3">
                Attach the existing inbound agent <strong>{{ $preferredAgentName }}</strong>
                on <strong>{{ $preferredAgentPhone }}</strong>.
                Connecting the workspace selects that agent automatically and syncs services, lawyers, and booking tools.
                Do not create a second agent.
            </p>
            @php
                $remoteAgents = $fineTuner['agents'] ?? [];
                $preferredId = $agentId;
                if (! $preferredId) {
                    $needle = strtolower($preferredAgentName);
                    $phoneDigits = preg_replace('/\D+/', '', $preferredAgentPhone) ?? '';
                    foreach ($remoteAgents as $remoteAgent) {
                        $agentPhone = preg_replace('/\D+/', '', (string) ($remoteAgent['phone_number'] ?? '')) ?? '';
                        $nameHit = $needle !== '' && str_contains(strtolower((string) ($remoteAgent['name'] ?? '')), $needle);
                        $phoneHit = $phoneDigits !== '' && $agentPhone !== '' && str_ends_with($agentPhone, $phoneDigits);
                        if ($nameHit || $phoneHit) {
                            $preferredId = $remoteAgent['id'];
                            break;
                        }
                    }
                }
            @endphp
            @if($connected && count($remoteAgents))
                <form method="POST" action="{{ route('ai.synthflow.assign') }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="ui-label">Synthflow agent</label>
                        <select name="model_id" class="ui-field" required>
                            @foreach($remoteAgents as $remoteAgent)
                                <option value="{{ $remoteAgent['id'] }}" @selected((string) $preferredId === (string) $remoteAgent['id'])>
                                    {{ $remoteAgent['name'] }}{{ filled($remoteAgent['phone_number'] ?? null) ? ' · '.$remoteAgent['phone_number'] : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="ui-label">Phone</label>
                        <input name="phone_number" class="ui-field" value="{{ old('phone_number', $phone ?: $preferredAgentPhone) }}" required>
                    </div>
                    <div class="col-md-3">
                        <button class="ui-btn ui-btn-primary">Use this agent</button>
                    </div>
                </form>
            @elseif($connected)
                <form method="POST" action="{{ route('ai.synthflow.assign') }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="ui-label">Existing agent ID</label>
                        <input name="model_id" class="ui-field" value="{{ old('model_id', $agentId) }}" placeholder="e01b…630d" required>
                    </div>
                    <div class="col-md-3">
                        <label class="ui-label">Phone</label>
                        <input name="phone_number" class="ui-field" value="{{ old('phone_number', $phone ?: $preferredAgentPhone) }}" required>
                    </div>
                    <div class="col-md-3">
                        <button class="ui-btn ui-btn-primary">Use this agent</button>
                    </div>
                </form>
            @else
                <p class="small ui-muted mb-3">Connect the workspace below with the API key and workspace ID from Synthflow Settings. The app will attach {{ $preferredAgentName }}.</p>
            @endif
            <div class="d-flex flex-wrap gap-2 mb-3">
                <form method="POST" action="{{ route('ai.synthflow.sync') }}">
                    @csrf
                    <button class="ui-btn ui-btn-brass" @if(! $hasAgent) disabled @endif>Sync services &amp; lawyers</button>
                </form>
                <form method="POST" action="{{ route('ai.synthflow.create-agent') }}">
                    @csrf
                    <input type="hidden" name="name" value="{{ $preferredAgentName }}">
                    <button class="ui-btn ui-btn-ghost" @if(! $connected) disabled @endif>Create a new agent instead</button>
                </form>
            </div>

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
    <section class="agent-card" x-data="{ prompt: @js($promptValue), defaults: @js($defaultSystemPrompt) }">
        <div class="agent-card-head">
            <div>
                <h3>AI agent (Synthflow)</h3>
                <p>Voice, language, and prompts. Sync services after you change catalog so callers get accurate fees.</p>
            </div>
            <span class="agent-prompt-badge" x-text="prompt.trim() === defaults.trim() ? 'Using default prompt' : 'Custom prompt'"></span>
        </div>

        <form id="ai-agent-form" method="POST" action="{{ route('ai.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="sync_now" value="1">
            <input type="hidden" name="ai_tone" value="{{ $firm->ai_tone ?: 'Professional' }}">
            <input type="hidden" name="after_hours_behavior" value="{{ $firm->after_hours_behavior ?: 'callback' }}">
            <input type="hidden" name="receptionist_name" value="{{ $firm->receptionist_name ?: 'Virtual Receptionist' }}">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ui-label">Language</label>
                    <select name="ai_language" class="ui-field" required>
                        @foreach([
                            'en' => 'English (US / UK / Global)',
                            'ur' => 'Urdu',
                            'en-ur' => 'English + Urdu',
                            'ar' => 'Arabic',
                            'hi' => 'Hindi',
                        ] as $code => $label)
                            <option value="{{ $code }}" @selected(old('ai_language', $firm->ai_language ?: 'en') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Voice ID <span class="opt">optional</span></label>
                    <input name="ai_voice" class="ui-field" value="{{ old('ai_voice', $firm->ai_voice) }}" placeholder="EXAVITQu4vr4xnSDxMaL">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="ui-check mb-2">
                        <input type="checkbox" class="ui-check-input" name="recording_enabled" value="1" @checked(old('recording_enabled', $firm->recording_enabled))>
                        <span>Call recording enabled</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="ui-label">First message <span class="opt">optional</span></label>
                    <textarea name="ai_greeting" class="ui-field" rows="3" required>{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Transfer number</label>
                    <input name="transfer_number" class="ui-field" value="{{ old('transfer_number', $firm->transfer_number) }}" placeholder="+1…">
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Allowed topics</label>
                    <input name="ai_allowed_topics" class="ui-field" value="{{ old('ai_allowed_topics', $firm->ai_allowed_topics) }}" placeholder="Services, fees, lawyers, booking">
                </div>
                <div class="col-12">
                    <label class="ui-label">Escalation rules</label>
                    <textarea name="ai_escalation_rules" class="ui-field" rows="2">{{ old('ai_escalation_rules', $firm->ai_escalation_rules) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                        <label class="ui-label mb-0">System prompt <span class="opt">optional</span></label>
                        <button type="button" class="ui-btn ui-btn-ghost" @click="prompt = defaults">Reset default</button>
                    </div>
                    <textarea name="ai_system_prompt" class="ui-field agent-prompt-editor" rows="16" x-model="prompt"></textarea>
                    <p class="small ui-muted mt-2 mb-0">
                        On update/sync we append live <strong>services &amp; lawyers</strong> under the prompt (like menu sync in the restaurant project).
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

    {{-- Catalog preview + test call --}}
    <div class="agent-grid agent-grid-secondary">
        <section class="agent-card">
            <div class="agent-card-head">
                <div>
                    <h3>Services &amp; lawyers (injected on sync)</h3>
                    <p>Preview of the catalog block appended to the Synthflow prompt.</p>
                </div>
            </div>
            <pre class="sf-catalog">{{ $catalogKnowledge }}</pre>
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
                            {{ \Illuminate\Support\Carbon::parse($lastSyncedAt)->timezone($firm->resolvedTimezone())->format('n/j/Y, g:i:s A') }}
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

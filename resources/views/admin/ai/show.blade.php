@extends('layouts.app')

@section('actions')
    <a href="{{ route('settings.edit') }}" class="btn btn-legal-secondary">Firm settings</a>
@endsection

@section('content')
@php
    $primaryAgent = collect($fineTuner['agents'] ?? [])->first(fn ($agent) => filled($agent['phone_number'] ?? null))
        ?? ($fineTuner['agents'][0] ?? null);
    $primaryNumber = $primaryAgent['phone_number'] ?? ($fineTuner['numbers'][0]['number'] ?? null);
@endphp

<section class="voice-studio mb-4">
    <div class="voice-studio-copy">
        <div class="studio-kicker">FineTuner voice line</div>
        <h2>{{ $primaryAgent['name'] ?? 'Connect your agent' }}</h2>
        <p class="voice-number">{{ $primaryNumber ?: 'Phone number not assigned' }}</p>
        <p>
            FineTuner supplies the phone number and the voice agent. This desk still owns leads, availability, and bookings.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="voice-pill {{ ($fineTuner['connected'] ?? false) ? 'is-live' : '' }}">
                {{ ($fineTuner['connected'] ?? false) ? 'Workspace connected' : 'Waiting for API key' }}
            </span>
            @if($primaryAgent)
                <span class="voice-pill">{{ ucfirst($primaryAgent['type'] ?: 'inbound') }}</span>
            @endif
        </div>
    </div>
    <div class="voice-studio-panel">
        @if($fineTuner['error'] ?? null)
            <div class="alert alert-danger">{{ $fineTuner['error'] }}</div>
        @endif

        <form method="POST" action="{{ route('ai.finetuner.connect') }}" class="mb-0">
            @csrf
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label">FineTuner API key</label>
                    <input type="password" name="api_key" class="form-control" placeholder="{{ $fineTunerKey ?: 'Bearer token from FineTuner' }}" autocomplete="off" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Workspace ID</label>
                    <input name="workspace_id" class="form-control" value="{{ old('workspace_id', $fineTunerWorkspace) }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Region</label>
                    <select name="base_url" class="form-select">
                        <option value="https://api.synthflow.ai/v2">Global</option>
                        <option value="https://api.us.synthflow.ai/v2">United States</option>
                        <option value="https://api.eu.synthflow.ai/v2">European Union</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-legal-brass mt-3">{{ $fineTunerKey ? 'Update connection' : 'Connect FineTuner' }}</button>
        </form>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <x-legal.card title="Phone numbers">
            @forelse($fineTuner['numbers'] as $number)
                <div class="line-row">
                    <div>
                        <div class="fw-semibold">{{ $number['number'] }}</div>
                        <div class="small text-muted">{{ $number['available'] ? 'Available for inbound' : 'Assigned' }}</div>
                    </div>
                    <x-legal.badge :tone="$number['available'] ? 'success' : 'brass'">
                        {{ $number['available'] ? 'Open' : 'In use' }}
                    </x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-3">
                    <p class="mb-0">Numbers appear here after FineTuner is connected.</p>
                </div>
            @endforelse
        </x-legal.card>
    </div>
    <div class="col-lg-7">
        <x-legal.card title="Agents">
            @forelse($fineTuner['agents'] as $agent)
                <div class="line-row">
                    <div>
                        <div class="fw-semibold">{{ $agent['name'] }}</div>
                        <div class="small text-muted">{{ $agent['phone_number'] ?: 'No number' }} · {{ $agent['description'] ?: ucfirst($agent['type']) }}</div>
                    </div>
                    <x-legal.badge tone="navy">{{ $agent['id'] }}</x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-3">
                    <p class="mb-0">Create an agent in FineTuner, then refresh this page.</p>
                </div>
            @endforelse

            @if(count($fineTuner['agents']) && count($fineTuner['numbers']))
                <form method="POST" action="{{ route('ai.finetuner.assign') }}" class="mt-3 pt-3 border-top">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Agent</label>
                            <select name="model_id" class="form-select" required>
                                @foreach($fineTuner['agents'] as $agent)
                                    <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Phone number</label>
                            <select name="phone_number" class="form-select" required>
                                @foreach($fineTuner['numbers'] as $number)
                                    <option value="{{ $number['number'] }}">{{ $number['number'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-legal-primary w-100">Attach</button>
                        </div>
                    </div>
                </form>
            @endif
        </x-legal.card>
    </div>
</div>

<div class="ai-hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="ai-kicker">
                <span class="dot"></span>
                AI Receptionist · {{ $online ? 'Online' : 'Standby' }}
            </div>
            <h2>{{ $online ? 'Your AI receptionist is ready.' : 'Your AI receptionist is standing by.' }}</h2>
            <p class="lead mb-3">
                {{ $firm->receptionist_name ?: 'Alex' }} answers inbound calls, explains approved services, captures leads, and books from verified availability — without inventing facts.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <x-legal.badge tone="brass">{{ $firm->ai_tone ?: 'Professional tone' }}</x-legal.badge>
                <x-legal.badge tone="navy">{{ strtoupper($firm->ai_language ?: 'en') }}</x-legal.badge>
                <x-legal.badge>{{ $aiStats['active_calls'] }} active call(s)</x-legal.badge>
            </div>
        </div>
        <div class="col-lg-5 text-center">
            <div class="ai-ring">
                <div class="ai-wave" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                </div>
            </div>
            <div class="small" style="color:rgba(246,243,237,.62)">
                {{ $online ? 'Listening for incoming calls' : 'Configure OpenAI / voice gateway to go live' }}
            </div>
        </div>
    </div>

    <div class="ai-metric-grid mt-4">
        <div class="ai-metric">
            <div class="label">Calls handled today</div>
            <div class="value">{{ $aiStats['calls_today'] }}</div>
        </div>
        <div class="ai-metric">
            <div class="label">Avg duration</div>
            <div class="value">{{ $aiStats['avg_duration'] ? gmdate('i:s', $aiStats['avg_duration']) : '—' }}</div>
        </div>
        <div class="ai-metric">
            <div class="label">Appointments booked</div>
            <div class="value">{{ $aiStats['bookings_today'] }}</div>
        </div>
        <div class="ai-metric">
            <div class="label">Human handoffs</div>
            <div class="value">{{ $aiStats['handoffs_today'] }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <x-legal.card title="Recent AI call activity">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Caller</th>
                            <th>Outcome</th>
                            <th>When</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentAiCalls as $call)
                        <tr>
                            <td class="fw-semibold">{{ $call->caller_phone }}</td>
                            <td>{{ $call->outcome ?: $call->status }}</td>
                            <td>{{ $call->started_at }}</td>
                            <td class="text-end"><a href="{{ route('calls.show', $call) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="legal-empty">No call activity yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>

    <div class="col-lg-5">
        <x-legal.card title="Voice gateway">
            <p class="small text-muted mb-2">Public Media Stream URL used in Twilio TwiML</p>
            <code class="d-block mb-3 small" style="word-break:break-all">{{ $publicWsUrl ?: 'Not configured' }}</code>
            @if(is_array($gatewayHealth))
                <x-legal.badge :tone="($gatewayHealth['ok'] ?? false) ? 'success' : 'danger'">
                    {{ ($gatewayHealth['ok'] ?? false) ? 'Gateway reachable' : 'Gateway unreachable' }}
                </x-legal.badge>
            @else
                <x-legal.badge>Health unchecked</x-legal.badge>
            @endif
            <hr>
            <div class="small text-muted mb-2">Leads captured by AI today</div>
            <div class="fw-bold fs-4 text-navy" style="color:var(--navy)">{{ $aiStats['leads_today'] }}</div>
        </x-legal.card>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <x-legal.card title="Persona & guardrails">
            <form method="POST" action="{{ route('ai.update') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Receptionist name</label>
                        <input name="receptionist_name" class="form-control" value="{{ old('receptionist_name', $firm->receptionist_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Language</label>
                        <input name="ai_language" class="form-control" value="{{ old('ai_language', $firm->ai_language) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Greeting</label>
                        <textarea name="ai_greeting" class="form-control" rows="3">{{ old('ai_greeting', $firm->ai_greeting) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tone</label>
                        <input name="ai_tone" class="form-control" value="{{ old('ai_tone', $firm->ai_tone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Transfer number</label>
                        <input name="transfer_number" class="form-control" value="{{ old('transfer_number', $firm->transfer_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">After-hours behavior</label>
                        <input name="after_hours_behavior" class="form-control" value="{{ old('after_hours_behavior', $firm->after_hours_behavior) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Escalation rules</label>
                        <textarea name="ai_escalation_rules" class="form-control" rows="3">{{ old('ai_escalation_rules', $firm->ai_escalation_rules) }}</textarea>
                    </div>
                </div>
                <button class="btn btn-legal-primary mt-3">Save AI settings</button>
            </form>
        </x-legal.card>
    </div>

    <div class="col-lg-6">
        <x-legal.card title="System prompt (generated)">
            <pre class="small mb-0" style="white-space:pre-wrap;color:var(--text-slate)">{{ $systemPrompt }}</pre>
        </x-legal.card>

        <x-legal.card title="Realtime tools" class="mt-3">
            <ul class="list-unstyled mb-0 small">
                @foreach($tools as $tool)
                    <li class="mb-2 pb-2 border-bottom">
                        <strong style="color:var(--navy)">{{ $tool['name'] }}</strong>
                        <div class="text-muted">{{ $tool['description'] }}</div>
                    </li>
                @endforeach
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

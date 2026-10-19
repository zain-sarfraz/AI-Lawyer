@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $lineAgent = collect($fineTuner['agents'] ?? [])->first(fn ($agent) => filled($agent['phone_number'] ?? null))
        ?? ($fineTuner['agents'][0] ?? null);
    $lineNumber = $lineAgent['phone_number'] ?? ($fineTuner['numbers'][0]['number'] ?? null);
    $leadTones = ['new' => 'info', 'contacted' => 'violet', 'qualified' => 'success', 'consultation_booked' => 'teal', 'follow_up' => 'warning', 'converted' => 'success', 'lost' => 'danger'];
    $avatarTones = ['#128a82', '#3b6d9a', '#3d4f73', '#c4a35a', '#c23b4a'];
@endphp

<section class="dash-hero mb-4">
    <div>
        <h1 class="font-display text-[clamp(1.75rem,3vw,2.35rem)] font-bold tracking-tight">{{ $greeting }}</h1>
        <p class="mt-1 text-(--muted)">Calls, bookings, and the Synthflow line in one place.</p>
    </div>
    <div class="dash-hero-visual">
        <svg class="dash-scales" viewBox="0 0 180 140" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="scaleMetal" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#5eead4"/>
                    <stop offset="100%" stop-color="#0d9488"/>
                </linearGradient>
            </defs>
            <path d="M90 18v96" stroke="url(#scaleMetal)" stroke-width="6" stroke-linecap="round"/>
            <path d="M48 42h84" stroke="url(#scaleMetal)" stroke-width="6" stroke-linecap="round"/>
            <circle cx="90" cy="18" r="8" fill="#14b8a6"/>
            <path d="M48 42l-22 40a18 18 0 1 0 36 0z" fill="#99f6e4" stroke="#0d9488" stroke-width="3"/>
            <path d="M132 42l-22 40a18 18 0 1 0 36 0z" fill="#5eead4" stroke="#0d9488" stroke-width="3"/>
            <rect x="70" y="112" width="40" height="10" rx="5" fill="#0b1e26"/>
        </svg>
        <a href="{{ route('ai.show') }}" class="ui-btn ui-btn-brass">
            <x-ui.icon name="headset" />
            Open voice line
        </a>
    </div>
</section>

<section class="finetuner-banner mb-4">
    <div>
        <div class="studio-kicker" style="color:#5eead4">Synthflow</div>
        <h2>{{ $lineNumber ?: 'No number attached' }}</h2>
        <p class="mt-1">
            @if($lineAgent)
                {{ $lineAgent['name'] }} is the live agent for this line.
            @elseif($fineTuner['connected'] ?? false)
                Synthflow is connected. Attach Qubetech AI Lawyer to this line.
            @else
                Connect Synthflow to load Qubetech AI Lawyer and +1 609 780 9610.
            @endif
        </p>
        <div class="voice-line-meta mt-4">
            <span class="voice-pill {{ ($fineTuner['connected'] ?? false) ? 'is-live' : '' }}">
                {{ ($fineTuner['connected'] ?? false) ? 'Connected' : 'Not connected' }}
            </span>
            <span class="voice-pill">{{ count($fineTuner['numbers'] ?? []) }} numbers</span>
            <span class="voice-pill">{{ count($fineTuner['agents'] ?? []) }} agents</span>
        </div>
    </div>
    <div class="finetuner-visual">
        <div class="ai-wave" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="finetuner-mic">
            <x-ui.icon name="mic" class="h-6 w-6" />
        </div>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <x-legal.kpi label="New leads" :value="$stats['leads_today']" hint="Captured today" tone="mint" icon="user-plus" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Appointments today" :value="$stats['appointments_today']" hint="On the calendar" tone="blue" icon="calendar-check" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Calls today" :value="$stats['calls_today']" hint="Inbound activity" tone="violet" icon="phone" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="AI bookings" :value="$stats['ai_bookings']" hint="Booked by receptionist" tone="amber" icon="broadcast" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Pending follow-ups" :value="$stats['pending_followups']" hint="Needs human attention" tone="rose" icon="clipboard" />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <x-legal.card>
            <x-slot:header>
                <h2 class="legal-card-title">Today’s appointments</h2>
            </x-slot:header>
            <x-slot:action>
                <a href="{{ route('appointments.index') }}" class="small">View all</a>
            </x-slot:action>

            @forelse($todaysAppointments as $appt)
                @php
                    $status = $appt->status?->value ?? 'scheduled';
                    $tone = in_array($status, ['pending', 'rescheduled'], true) ? 'pending' : ($appt->source === 'phone_ai' ? 'teal' : 'navy');
                @endphp
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $appt->start_at?->format('g:i A') }} · {{ $appt->lead?->name ?: $appt->lead?->phone ?: 'Caller' }}</div>
                        <div class="small ui-muted">{{ $appt->service?->name }} · {{ $appt->lawyer?->displayName() }}</div>
                    </div>
                    <x-legal.badge :tone="$tone">
                        {{ $appt->source === 'phone_ai' ? 'AI booked' : $status }}
                    </x-legal.badge>
                </div>
            @empty
                <div class="legal-empty">
                    <h3>No appointments today</h3>
                    <p class="mb-0">The calendar is clear for the rest of the day.</p>
                </div>
            @endforelse
        </x-legal.card>
    </div>

    <div class="col-lg-4">
        <x-legal.card title="AI receptionist activity">
            <div class="activity-stat">
                <div class="activity-stat-icon"><x-ui.icon name="phone" /></div>
                <div class="flex-grow-1">
                    <div class="small ui-muted">Calls handled</div>
                    <div class="fw-bold text-lg">{{ $stats['calls_today'] }}</div>
                </div>
            </div>
            <div class="activity-stat">
                <div class="activity-stat-icon"><x-ui.icon name="user-plus" /></div>
                <div class="flex-grow-1">
                    <div class="small ui-muted">Leads captured</div>
                    <div class="fw-bold">{{ $stats['leads_today'] }}</div>
                </div>
            </div>
            <div class="activity-stat">
                <div class="activity-stat-icon"><x-ui.icon name="calendar-check" /></div>
                <div class="flex-grow-1">
                    <div class="small ui-muted">Booked</div>
                    <div class="fw-bold">{{ $stats['ai_bookings'] }}</div>
                </div>
            </div>
            <div class="activity-stat">
                <div class="activity-stat-icon"><x-ui.icon name="headset" /></div>
                <div class="flex-grow-1">
                    <div class="small ui-muted">Handoffs</div>
                    <div class="fw-bold">{{ $stats['handoffs_today'] }}</div>
                </div>
            </div>
            <a href="{{ route('ai.show') }}" class="ui-btn ui-btn-primary w-full mt-3">
                <x-ui.icon name="headset" />
                Open AI Receptionist
            </a>
        </x-legal.card>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <x-legal.card>
            <x-slot:header>
                <h2 class="legal-card-title">Recent calls</h2>
            </x-slot:header>
            <x-slot:action>
                <a href="{{ route('calls.index') }}" class="small">Call log</a>
            </x-slot:action>

            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Caller</th>
                            <th>Intent</th>
                            <th>Duration</th>
                            <th>AI outcome</th>
                            <th>Date/time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentCalls as $call)
                        <tr>
                            <td>
                                <a class="fw-semibold" href="{{ route('calls.show', $call) }}">{{ $call->caller_phone }}</a>
                            </td>
                            <td><code>{{ $call->intent ?: '—' }}</code></td>
                            <td>{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '—' }}</td>
                            <td><code>{{ $call->outcome ?: '—' }}</code></td>
                            <td>{{ $call->started_at }}</td>
                            <td>
                                <x-legal.badge :tone="$call->status === 'completed' ? 'success' : 'soft'">
                                    {{ $call->status }}
                                </x-legal.badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="legal-empty">No calls yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <x-legal.card title="Follow-ups / tasks">
            @forelse($openCallbacks as $callback)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $callback->name ?: $callback->phone }}</div>
                        <div class="small ui-muted">{{ $callback->reason ?: 'Callback requested' }}</div>
                    </div>
                    <x-legal.badge tone="danger">Open</x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-4">
                    <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-teal-500/10 text-teal-600">
                        <x-ui.icon name="clipboard" class="h-6 w-6" />
                    </div>
                    <h3>No open follow-ups</h3>
                    <p class="mb-0">The desk is clear. Great work — keep it up.</p>
                </div>
            @endforelse
        </x-legal.card>
    </div>
    <div class="col-lg-7">
        <x-legal.card>
            <x-slot:header>
                <h2 class="legal-card-title">Recent leads</h2>
            </x-slot:header>
            <x-slot:action>
                <a href="{{ route('leads.index') }}" class="small">Pipeline</a>
            </x-slot:action>

            @forelse($recentLeads as $index => $lead)
                @php
                    $status = $lead->status?->value ?? (string) $lead->status;
                    $tone = $leadTones[$status] ?? 'soft';
                    $name = $lead->name ?: $lead->phone ?: 'Unknown';
                    $leadInitials = collect(explode(' ', $name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                @endphp
                <div class="legal-list-item">
                    <div class="d-flex align-items-center gap-2">
                        <div class="lead-avatar" style="background: {{ $avatarTones[$index % count($avatarTones)] }}">{{ strtoupper($leadInitials) }}</div>
                        <div>
                            <a class="fw-semibold" href="{{ route('leads.show', $lead) }}">{{ $name }}</a>
                            <div class="small ui-muted">{{ $lead->service?->name ?: 'Service TBD' }} · {{ $lead->source ?: 'manual' }}</div>
                        </div>
                    </div>
                    <x-legal.badge :tone="$tone">{{ $status }}</x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-4"><p class="mb-0">No leads yet.</p></div>
            @endforelse
        </x-legal.card>
    </div>
</div>
@endsection

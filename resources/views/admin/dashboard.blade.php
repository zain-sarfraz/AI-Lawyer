@extends('layouts.app')

@section('actions')
    <a href="{{ route('appointments.create') }}" class="ui-btn ui-btn-primary">Book appointment</a>
@endsection

@section('content')
<div class="studio-headline mb-4">
    <div>
        <div class="studio-kicker">Today</div>
        <h1>{{ $greeting }}</h1>
        <p>Calls, bookings, and the FineTuner line in one place.</p>
    </div>
    <a href="{{ route('ai.show') }}" class="ui-btn ui-btn-primary">Open voice line</a>
</div>

@php
    $lineAgent = collect($fineTuner['agents'] ?? [])->first(fn ($agent) => filled($agent['phone_number'] ?? null))
        ?? ($fineTuner['agents'][0] ?? null);
    $lineNumber = $lineAgent['phone_number'] ?? ($fineTuner['numbers'][0]['number'] ?? null);
@endphp

<section class="voice-line mb-4">
    <div>
        <div class="studio-kicker">FineTuner</div>
        <h2>{{ $lineNumber ?: 'No number attached' }}</h2>
        <p>
            @if($lineAgent)
                {{ $lineAgent['name'] }} is the live agent for this line.
            @elseif($fineTuner['connected'] ?? false)
                FineTuner is connected. Attach a number to an agent.
            @else
                Connect FineTuner to load your phone number and voice agent.
            @endif
        </p>
    </div>
    <div class="voice-line-meta">
        <span class="voice-pill {{ ($fineTuner['connected'] ?? false) ? 'is-live' : '' }}">
            {{ ($fineTuner['connected'] ?? false) ? 'Connected' : 'Not connected' }}
        </span>
        <span>{{ count($fineTuner['numbers'] ?? []) }} numbers</span>
        <span>{{ count($fineTuner['agents'] ?? []) }} agents</span>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <x-legal.kpi label="New leads" :value="$stats['leads_today']" hint="Captured today" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Appointments today" :value="$stats['appointments_today']" hint="On the calendar" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Calls today" :value="$stats['calls_today']" hint="Inbound activity" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="AI bookings" :value="$stats['ai_bookings']" hint="Booked by receptionist" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Pending follow-ups" :value="$stats['pending_followups']" hint="Needs human attention" />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <x-legal.card>
            <x-slot:header>
                <h2 class="legal-card-title">Today’s appointments</h2>
            </x-slot:header>
            <x-slot:action>
                <a href="{{ route('appointments.index') }}" class="small">View all</a>
            </x-slot:action>

            @forelse($todaysAppointments as $appt)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $appt->start_at?->format('g:i A') }} · {{ $appt->lead?->name ?: $appt->lead?->phone ?: 'Caller' }}</div>
                        <div class="small ui-muted">{{ $appt->service?->name }} · {{ $appt->lawyer?->displayName() }}</div>
                    </div>
                    <x-legal.badge :tone="$appt->source === 'phone_ai' ? 'brass' : 'navy'">
                        {{ $appt->source === 'phone_ai' ? 'AI booked' : ($appt->status?->value ?? 'scheduled') }}
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

    <div class="col-lg-5">
        <x-legal.card title="AI receptionist activity">
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="legal-kpi">
                        <div class="label">Calls handled</div>
                        <div class="value">{{ $stats['calls_today'] }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="legal-kpi">
                        <div class="label">Leads captured</div>
                        <div class="value">{{ $stats['leads_today'] }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="legal-kpi">
                        <div class="label">Booked</div>
                        <div class="value">{{ $stats['ai_bookings'] }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="legal-kpi">
                        <div class="label">Handoffs</div>
                        <div class="value">{{ $stats['handoffs_today'] }}</div>
                    </div>
                </div>
            </div>
            <a href="{{ route('ai.show') }}" class="ui-btn ui-btn-brass btn-sm">Open AI Receptionist</a>
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
                            <td>{{ $call->intent ?: '—' }}</td>
                            <td>{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '—' }}</td>
                            <td>{{ $call->outcome ?: '—' }}</td>
                            <td>{{ $call->started_at }}</td>
                            <td><x-legal.badge>{{ $call->status }}</x-legal.badge></td>
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
                    <p class="mb-0">No open follow-ups. The desk is clear.</p>
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

            @forelse($recentLeads as $lead)
                <div class="legal-list-item">
                    <div>
                        <a class="fw-semibold" href="{{ route('leads.show', $lead) }}">{{ $lead->name ?: $lead->phone ?: 'Unknown' }}</a>
                        <div class="small ui-muted">{{ $lead->service?->name ?: 'Service TBD' }} · {{ $lead->source ?: 'manual' }}</div>
                    </div>
                    <x-legal.badge>{{ $lead->status?->value ?? $lead->status }}</x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-4"><p class="mb-0">No leads yet.</p></div>
            @endforelse
        </x-legal.card>
    </div>
</div>
@endsection

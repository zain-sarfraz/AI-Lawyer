@extends('layouts.app')

@section('actions')
<a href="{{ route('leads.edit', $lead) }}" class="ui-btn ui-btn-ghost">Edit</a>
@endsection

@section('content')
<x-legal.page-header :title="$lead->name ?: 'Lead'" subtitle="Intake profile, history, and next actions" />

<div class="row g-3">
    <div class="col-lg-8">
        <x-legal.card title="Profile">
            <div class="legal-meta-grid">
                <div class="legal-meta-item">
                    <label>Phone</label>
                    <div>{{ $lead->phone ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Email</label>
                    <div>{{ $lead->email ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Status</label>
                    <div><x-legal.badge>{{ str_replace('_', ' ', $lead->status?->value ?? $lead->status) }}</x-legal.badge></div>
                </div>
                <div class="legal-meta-item">
                    <label>Source</label>
                    <div>{{ $lead->source ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Service interest</label>
                    <div>{{ $lead->service?->name ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Assigned lawyer</label>
                    <div>{{ $lead->lawyer?->displayName() ?: 'Unassigned' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Last contact</label>
                    <div>{{ $lead->last_contact_at?->format('M j, Y g:i A') ?: '—' }}</div>
                </div>
            </div>
            @if($lead->notes)
                <hr class="my-3" style="border-color:var(--line);opacity:1">
                <div class="legal-meta-item">
                    <label>Notes</label>
                    <div style="font-weight:500;white-space:pre-wrap">{{ $lead->notes }}</div>
                </div>
            @endif
        </x-legal.card>

        <x-legal.card title="Appointments" class="mt-3">
            @forelse($lead->appointments as $appt)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $appt->start_at?->format('D, M j · g:i A') }}</div>
                        <div class="small ui-muted">{{ $appt->service?->name ?: 'Consultation' }}</div>
                    </div>
                    <x-legal.badge tone="navy">{{ $appt->status?->value }}</x-legal.badge>
                </div>
            @empty
                <div class="legal-empty py-3">No appointments yet.</div>
            @endforelse
        </x-legal.card>

        <x-legal.card title="Call history" class="mt-3">
            @forelse($lead->calls as $call)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $call->started_at?->format('M j, Y g:i A') ?: '—' }}</div>
                        <div class="small ui-muted">{{ $call->intent ?: 'General inquiry' }} · {{ $call->outcome ?: $call->status }}</div>
                    </div>
                    <a href="{{ route('calls.show', $call) }}">Open</a>
                </div>
            @empty
                <div class="legal-empty py-3">No linked calls.</div>
            @endforelse
        </x-legal.card>
    </div>

    <div class="col-lg-4">
        <x-legal.card title="Activity">
            <ul class="legal-timeline">
                @php
                    $events = collect();
                    foreach ($lead->calls as $call) {
                        $events->push([
                            'at' => $call->started_at,
                            'title' => 'AI receptionist handled call',
                            'detail' => $call->intent ?: ($call->outcome ?: 'Inbound call'),
                        ]);
                    }
                    foreach ($lead->appointments as $appt) {
                        $events->push([
                            'at' => $appt->created_at ?? $appt->start_at,
                            'title' => 'Appointment ' . ($appt->status?->value ?? 'scheduled'),
                            'detail' => ($appt->service?->name ?: 'Consultation') . ' · ' . optional($appt->start_at)->format('M j, g:i A'),
                        ]);
                    }
                    if ($lead->created_at) {
                        $events->push([
                            'at' => $lead->created_at,
                            'title' => 'Lead captured',
                            'detail' => $lead->source ? 'Source: ' . $lead->source : 'Added to pipeline',
                        ]);
                    }
                    $events = $events->filter(fn ($e) => filled($e['at']))->sortByDesc('at')->take(12);
                @endphp

                @forelse($events as $event)
                    <li>
                        <time>{{ $event['at']->format('g:i A · M j') }}</time>
                        <strong>{{ $event['title'] }}</strong>
                        <span>{{ $event['detail'] }}</span>
                    </li>
                @empty
                    <li>
                        <time>Now</time>
                        <strong>Waiting for activity</strong>
                        <span>Calls and bookings will appear here.</span>
                    </li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

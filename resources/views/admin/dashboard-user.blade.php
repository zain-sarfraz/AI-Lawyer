@extends('layouts.app')

@section('title', 'My dashboard')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-[clamp(1.75rem,3vw,2.2rem)] font-extrabold tracking-tight">{{ $greeting }}</h1>
    <p class="mt-1 text-(--muted)">{{ $roleLabel }} desk at {{ $firmShort }} — your appointments and records.</p>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <x-legal.kpi label="Today" :value="$todayCount" hint="Appointments today" tone="blue" icon="calendar-check" />
    </div>
    <div class="col-6 col-xl-3">
        <x-legal.kpi label="Upcoming" :value="$upcomingAppointments->count()" hint="Next on your calendar" tone="mint" icon="calendar" />
    </div>
    <div class="col-6 col-xl-3">
        <x-legal.kpi label="Clients" :value="$clients->count()" hint="Recent records" tone="teal" icon="users" />
    </div>
    <div class="col-6 col-xl-3">
        <x-legal.kpi label="Cases" :value="$cases->count()" hint="Active matters" tone="violet" icon="scales" />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-7">
        <x-legal.card>
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h2 class="legal-card-title mb-0">My appointments</h2>
                <a href="{{ route('appointments.index') }}" class="small">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-(--line) text-left text-(--muted)">
                            <th class="py-2 pr-3">When</th>
                            <th class="py-2 pr-3">With</th>
                            <th class="py-2 pr-3">Type</th>
                            <th class="py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingAppointments as $appt)
                            @php
                                $with = $appt->client?->name
                                    ?: $appt->lead?->name
                                    ?: '—';
                                $counterpart = $appt->lawyer?->user?->name;
                                if ($counterpart && auth()->user()?->lawyer?->id !== $appt->lawyer_id) {
                                    $with = $with.' · '.$counterpart;
                                } elseif ($counterpart && ! $appt->client && ! $appt->lead) {
                                    $with = $counterpart;
                                }
                            @endphp
                            <tr class="border-b border-(--line)/60">
                                <td class="py-3 pr-3">
                                    <div class="font-semibold">{{ optional($appt->start_at)->format('D, M j') }}</div>
                                    <div class="text-xs text-(--muted)">{{ optional($appt->start_at)->format('g:i A') }} – {{ optional($appt->end_at)->format('g:i A') }}</div>
                                </td>
                                <td class="py-3 pr-3 font-semibold">{{ $with }}</td>
                                <td class="py-3 pr-3 text-(--muted)">{{ $appt->appointmentType?->name ?: ($appt->service?->name ?: '—') }}</td>
                                <td class="py-3"><span class="voice-pill">{{ $appt->status?->value ?? $appt->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-(--muted)">No upcoming appointments on your calendar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>

    <div class="col-12 col-lg-5">
        <x-legal.card class="mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h2 class="legal-card-title mb-0">My records</h2>
                <a href="{{ route('crm.documents.index') }}" class="small">Documents</a>
            </div>
            <ul class="space-y-2 text-sm">
                @forelse($documents as $doc)
                    <li class="flex justify-content-between gap-2 border-b border-(--line)/50 py-2">
                        <span class="font-semibold truncate">{{ $doc->title ?? $doc->original_name ?? 'Document' }}</span>
                        <span class="text-xs text-(--muted) shrink-0">{{ $doc->client?->name ?: '—' }}</span>
                    </li>
                @empty
                    <li class="py-3 text-(--muted)">No documents yet. Upload from CRM when you need to keep a record.</li>
                @endforelse
            </ul>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="{{ route('cases.index') }}" class="ui-btn ui-btn-ghost btn-sm">Cases</a>
                <a href="{{ route('appointments.index') }}" class="ui-btn ui-btn-ghost btn-sm">Appointments</a>
                <a href="{{ route('crm.documents.index') }}" class="ui-btn ui-btn-brass btn-sm">Add / view records</a>
            </div>
        </x-legal.card>

        <x-legal.card>
            <h2 class="legal-card-title mb-3">My clients (from appointments)</h2>
            <ul class="space-y-2 text-sm">
                @forelse($clients as $client)
                    <li class="flex justify-content-between gap-2 border-b border-(--line)/50 py-2">
                        <span class="font-semibold">{{ $client->name }}</span>
                        <span class="text-xs text-(--muted)">{{ $client->status?->value ?? '' }}</span>
                    </li>
                @empty
                    <li class="py-3 text-(--muted)">Clients appear here after appointments are booked with you.</li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

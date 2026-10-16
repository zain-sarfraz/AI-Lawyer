@extends('layouts.app')

@section('content')
<x-legal.page-header :title="$client->name" :subtitle="($client->client_number ?: 'Client').' · AI Lawyer matter record'">
    <x-slot:actions>
        <a class="ui-btn ui-btn-ghost" href="{{ route('clients.edit', $client) }}">Edit</a>
    </x-slot:actions>
</x-legal.page-header>

<div class="row g-3">
    <div class="col-lg-7">
        <x-legal.card title="Client details">
            <div class="legal-meta-grid">
                <div class="legal-meta-item">
                    <label>Name</label>
                    <div>{{ $client->name }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Client number</label>
                    <div>{{ $client->client_number ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Phone number</label>
                    <div>{{ $client->phone ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Email</label>
                    <div>{{ $client->email ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Status</label>
                    <div><x-legal.badge tone="navy">{{ $client->status?->value }}</x-legal.badge></div>
                </div>
                <div class="legal-meta-item">
                    <label>Services</label>
                    <div>{{ $client->services->pluck('name')->join(', ') ?: '—' }}</div>
                </div>
            </div>
            <hr class="my-3" style="border-color:var(--line);opacity:1">
            <div class="legal-meta-item">
                <label>Notes</label>
                <div style="font-weight:500;white-space:pre-wrap">{{ $client->notes ?: 'No notes yet. Edit the client to add notes.' }}</div>
            </div>
        </x-legal.card>
    </div>

    <div class="col-lg-5">
        <x-legal.card title="Linked leads">
            @forelse($client->leads as $lead)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $lead->name ?: $lead->phone }}</div>
                        <div class="small ui-muted">{{ str_replace('_', ' ', $lead->status?->value ?? '') }}</div>
                    </div>
                    <a href="{{ route('leads.show', $lead) }}">Open</a>
                </div>
            @empty
                <div class="legal-empty py-3">No linked leads.</div>
            @endforelse
        </x-legal.card>
    </div>

    <div class="col-12">
        <x-legal.card title="Appointments">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($client->appointments as $appt)
                        <tr>
                            <td class="fw-semibold">{{ $appt->start_at }}</td>
                            <td>{{ $appt->service?->name ?: '—' }}</td>
                            <td><x-legal.badge tone="navy">{{ $appt->status?->value }}</x-legal.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="legal-empty">No appointments on record.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>
</div>
@endsection

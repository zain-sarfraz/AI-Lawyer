@extends('layouts.app')

@section('actions')
<a class="btn btn-legal-secondary" href="{{ route('clients.edit', $client) }}">Edit</a>
@endsection

@section('content')
<x-legal.page-header :title="$client->name" subtitle="Client overview and matter history" />

<div class="legal-tabs mb-3">
    <a href="#overview" class="active">Overview</a>
    <a href="#appointments">Appointments</a>
    <a href="#leads">Linked leads</a>
</div>

<div class="row g-3">
    <div class="col-lg-7" id="overview">
        <x-legal.card title="Client overview">
            <div class="legal-meta-grid">
                <div class="legal-meta-item">
                    <label>Phone</label>
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
                    <label>Created</label>
                    <div>{{ $client->created_at?->format('M j, Y') ?: '—' }}</div>
                </div>
            </div>
            @if($client->notes)
                <hr class="my-3" style="border-color:var(--line);opacity:1">
                <div class="legal-meta-item">
                    <label>Notes</label>
                    <div style="font-weight:500;white-space:pre-wrap">{{ $client->notes }}</div>
                </div>
            @endif
        </x-legal.card>
    </div>

    <div class="col-lg-5" id="leads">
        <x-legal.card title="Linked leads">
            @forelse($client->leads as $lead)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $lead->name ?: $lead->phone }}</div>
                        <div class="small text-muted">{{ str_replace('_', ' ', $lead->status?->value ?? '') }}</div>
                    </div>
                    <a href="{{ route('leads.show', $lead) }}">Open</a>
                </div>
            @empty
                <div class="legal-empty py-3">No linked leads.</div>
            @endforelse
        </x-legal.card>
    </div>

    <div class="col-12" id="appointments">
        <x-legal.card title="Appointments">
            <div class="table-responsive">
                <table class="table align-middle">
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

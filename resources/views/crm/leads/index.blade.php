@extends('layouts.app')

@section('actions')
<a href="{{ route('leads.create') }}" class="btn btn-legal-primary">Create lead</a>
@endsection

@section('content')
<x-legal.page-header title="Leads" subtitle="Intake pipeline for prospective clients" />

<form class="legal-toolbar">
    <div class="flex-grow-1" style="min-width:220px">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Search name, phone, email">
    </div>
    <div style="min-width:180px">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['new','contacted','qualified','consultation_booked','converted','lost','follow_up'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-legal-secondary">Filter</button>
</form>

<x-legal.card>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Source</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Last contact</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td class="fw-semibold">{{ $lead->name ?: '—' }}</td>
                    <td>{{ $lead->phone }}</td>
                    <td>{{ $lead->email ?: '—' }}</td>
                    <td>{{ $lead->source ?: '—' }}</td>
                    <td>{{ $lead->service?->name ?: '—' }}</td>
                    <td><x-legal.badge>{{ str_replace('_', ' ', $lead->status?->value ?? $lead->status) }}</x-legal.badge></td>
                    <td>{{ $lead->last_contact_at ?: '—' }}</td>
                    <td class="text-end"><a href="{{ route('leads.show', $lead) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="legal-empty">No leads found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $leads->withQueryString()->links() }}</div>
</x-legal.card>
@endsection

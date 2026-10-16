@extends('layouts.app')

@section('content')
<div x-data="liveTableSearch(@js($filters['q'] ?? ''), @js($filters['status'] ?? ''))">
    <x-legal.page-header title="Leads" subtitle="Intake pipeline for prospective clients">
        <x-slot:actions>
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-lead')">Create lead</button>
        </x-slot:actions>
    </x-legal.page-header>

    <div class="legal-toolbar">
        <div class="legal-toolbar-search">
            <input
                type="search"
                class="ui-field"
                placeholder="Search name, phone, email"
                autocomplete="off"
                x-model="query"
                @input="apply()"
            >
        </div>
        <select class="ui-field" x-model="status" @change="apply()">
            <option value="">All statuses</option>
            @foreach(['new','contacted','qualified','consultation_booked','converted','lost','follow_up'] as $status)
                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
            @endforeach
        </select>
    </div>

    <x-legal.card>
        <div class="ui-table-wrap">
            <table class="ui-table">
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
                <tbody x-ref="tbody">
                @forelse($leads as $lead)
                    @php
                        $statusValue = $lead->status?->value ?? (string) $lead->status;
                        $search = strtolower(trim(implode(' ', array_filter([
                            $lead->name,
                            $lead->phone,
                            $lead->email,
                            $lead->source,
                            $lead->service?->name,
                            str_replace('_', ' ', $statusValue),
                        ]))));
                    @endphp
                    <tr data-search="{{ $search }}" data-status="{{ $statusValue }}">
                        <td class="fw-semibold">{{ $lead->name ?: '—' }}</td>
                        <td>{{ $lead->phone }}</td>
                        <td>{{ $lead->email ?: '—' }}</td>
                        <td>{{ $lead->source ?: '—' }}</td>
                        <td>{{ $lead->service?->name ?: '—' }}</td>
                        <td><x-legal.badge>{{ str_replace('_', ' ', $statusValue) }}</x-legal.badge></td>
                        <td>{{ $lead->last_contact_at ?: '—' }}</td>
                        <td class="text-end"><a href="{{ route('leads.show', $lead) }}">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="legal-empty">No leads found.</td></tr>
                @endforelse
                    <tr x-ref="empty" hidden>
                        <td colspan="8" class="legal-empty">No matching leads.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3" x-ref="pager">{{ $leads->withQueryString()->links() }}</div>
    </x-legal.card>
</div>

@include('crm.leads._create-modal')
@endsection

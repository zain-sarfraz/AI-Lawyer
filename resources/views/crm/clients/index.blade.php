@extends('layouts.app')

@section('actions')
<a href="{{ route('clients.create') }}" class="ui-btn ui-btn-primary">New client</a>
@endsection

@section('content')
<x-legal.page-header title="Clients" subtitle="Formal client records separate from leads" />

<form class="legal-toolbar">
    <div class="flex-grow-1" style="min-width:220px">
        <input class="ui-field" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search clients">
    </div>
    <button class="ui-btn ui-btn-ghost">Filter</button>
</form>

<x-legal.card>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td class="fw-semibold">{{ $client->name }}</td>
                    <td>{{ $client->phone ?: '—' }}</td>
                    <td>{{ $client->email ?: '—' }}</td>
                    <td><x-legal.badge>{{ $client->status?->value }}</x-legal.badge></td>
                    <td class="text-end"><a href="{{ route('clients.show', $client) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="legal-empty">No clients yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $clients->withQueryString()->links() }}</div>
</x-legal.card>
@endsection

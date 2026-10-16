@extends('layouts.app')

@section('content')
<div x-data="liveTableSearch(@js($filters['q'] ?? ''))">
    <x-legal.page-header title="Calls" subtitle="Inbound voice history, transcripts, and outcomes" />

    <div class="legal-toolbar">
        <div class="legal-toolbar-search">
            <input
                type="search"
                class="ui-field"
                placeholder="Search by phone, intent, outcome"
                autocomplete="off"
                x-model="query"
                @input="apply()"
            >
        </div>
    </div>

    <x-legal.card>
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Caller</th>
                        <th>Phone</th>
                        <th>Intent</th>
                        <th>Duration</th>
                        <th>Outcome</th>
                        <th>AI/Human</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                @forelse($calls as $call)
                    @php
                        $search = strtolower(trim(implode(' ', array_filter([
                            $call->caller_phone,
                            $call->intent,
                            $call->outcome,
                            (string) $call->started_at,
                        ]))));
                    @endphp
                    <tr data-search="{{ $search }}">
                        <td class="fw-semibold">{{ $call->caller_phone }}</td>
                        <td>{{ $call->caller_phone }}</td>
                        <td>{{ $call->intent ?: '—' }}</td>
                        <td>{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '—' }}</td>
                        <td>{{ $call->outcome ?: '—' }}</td>
                        <td><x-legal.badge tone="brass">AI</x-legal.badge></td>
                        <td>{{ $call->started_at }}</td>
                        <td class="text-end"><a href="{{ route('calls.show', $call) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="legal-empty">No calls yet.</td></tr>
                @endforelse
                    <tr x-ref="empty" hidden>
                        <td colspan="8" class="legal-empty">No matching calls.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3" x-ref="pager">{{ $calls->withQueryString()->links() }}</div>
    </x-legal.card>
</div>
@endsection

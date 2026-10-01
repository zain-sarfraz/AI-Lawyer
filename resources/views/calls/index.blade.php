@extends('layouts.app')

@section('content')
<x-legal.page-header title="Calls" subtitle="Inbound voice history, transcripts, and outcomes" />

<form class="legal-toolbar">
    <div class="flex-grow-1" style="min-width:220px">
        <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by phone">
    </div>
    <button class="btn btn-legal-secondary">Filter</button>
</form>

<x-legal.card>
    <div class="table-responsive">
        <table class="table align-middle">
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
            <tbody>
            @forelse($calls as $call)
                <tr>
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
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $calls->withQueryString()->links() }}</div>
</x-legal.card>
@endsection

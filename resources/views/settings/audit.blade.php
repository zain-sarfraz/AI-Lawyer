@extends('layouts.app')

@section('content')
<x-legal.page-header title="Audit logs" subtitle="Security and operational change history" />

<x-legal.card>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="fw-semibold">{{ $log->created_at }}</td>
                    <td>{{ $log->user?->name ?: 'system' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ class_basename((string) $log->entity_type) }} #{{ $log->entity_id }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="legal-empty">No audit events yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</x-legal.card>
@endsection

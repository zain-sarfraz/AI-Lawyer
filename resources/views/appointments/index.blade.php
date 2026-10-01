@extends('layouts.app')

@section('actions')
<a href="{{ route('appointments.create') }}" class="btn btn-legal-primary">Book appointment</a>
@endsection

@section('content')
<x-legal.page-header title="Appointments" subtitle="Verified consultations across the firm calendar" />

<x-legal.card>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Caller</th>
                    <th>Service</th>
                    <th>Lawyer</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($appointments as $appointment)
                <tr>
                    <td class="fw-semibold">{{ $appointment->start_at }}</td>
                    <td>{{ $appointment->lead?->name ?: $appointment->lead?->phone ?: '—' }}</td>
                    <td>{{ $appointment->service?->name }}</td>
                    <td>{{ $appointment->lawyer?->displayName() }}</td>
                    <td>
                        <x-legal.badge :tone="$appointment->source === 'phone_ai' ? 'brass' : 'soft'">
                            {{ $appointment->source === 'phone_ai' ? 'AI booked' : ($appointment->source ?: 'staff') }}
                        </x-legal.badge>
                    </td>
                    <td><x-legal.badge tone="navy">{{ $appointment->status?->value }}</x-legal.badge></td>
                    <td class="text-end">
                        @if(in_array($appointment->status?->value, ['scheduled','confirmed','in_progress'], true))
                            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
                                @csrf
                                <button class="btn btn-sm btn-legal-danger">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="legal-empty">No appointments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $appointments->links() }}</div>
</x-legal.card>
@endsection

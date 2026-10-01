@extends('layouts.app')

@section('actions')
<a href="{{ route('services.create') }}" class="ui-btn ui-btn-primary">New service</a>
@endsection

@section('content')
<x-legal.page-header title="Services" subtitle="Catalog pricing, duration, and AI booking visibility" />

<x-legal.card>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>AI</th>
                    <th>Booking</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($services as $service)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $service->name }}</div>
                        <div class="small ui-muted">{{ $service->category?->name ?: 'Uncategorized' }}</div>
                    </td>
                    <td>{{ $service->currency }} {{ number_format((float) $service->price, 2) }}</td>
                    <td>{{ $service->duration_minutes }} min</td>
                    <td>
                        <x-legal.badge :tone="$service->ai_enabled ? 'brass' : 'soft'">
                            {{ $service->ai_enabled ? 'Visible' : 'Hidden' }}
                        </x-legal.badge>
                    </td>
                    <td>
                        <x-legal.badge :tone="$service->booking_enabled ? 'navy' : 'soft'">
                            {{ $service->booking_enabled ? 'Enabled' : 'Disabled' }}
                        </x-legal.badge>
                    </td>
                    <td class="text-end"><a href="{{ route('services.edit', $service) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="legal-empty">No services yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $services->links() }}</div>
</x-legal.card>
@endsection

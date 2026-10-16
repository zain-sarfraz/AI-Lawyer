@extends('layouts.app')

@section('content')
<x-legal.page-header title="Services" subtitle="Catalog pricing, duration, and AI booking visibility">
    <x-slot:actions>
        <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-service')">New service</button>
    </x-slot:actions>
</x-legal.page-header>

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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($services as $service)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $service->name }}</div>
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
                    <td class="text-end">
                        <div class="table-icon-actions">
                            <button
                                type="button"
                                class="table-icon-btn is-edit"
                                title="Edit"
                                aria-label="Edit {{ $service->name }}"
                                @click="$store.serviceUi.openEdit({{ $service->id }})"
                            >
                                <x-ui.icon name="pencil" class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="table-icon-btn is-delete"
                                title="Delete"
                                aria-label="Delete {{ $service->name }}"
                                @click="$store.serviceUi.askDelete({ id: {{ $service->id }}, name: @js($service->name), url: @js(route('services.destroy', $service)) })"
                            >
                                <x-ui.icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="legal-empty">No services yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $services->links() }}</div>
</x-legal.card>

@include('admin.services._create-modal')
@include('admin.services._delete-modal')

@foreach($services as $service)
    @include('admin.services._edit-modal', ['service' => $service->loadMissing('lawyers')])
@endforeach
@endsection

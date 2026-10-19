@extends('layouts.app')

@section('content')
@php
    $clientCards = $clients->getCollection()->map(function ($client) {
        $appointments = $client->appointments->map(function ($appt) {
            $status = $appt->status?->value ?? (string) $appt->status;

            return [
                'id' => $appt->id,
                'when' => $appt->start_at?->format('M j, Y g:i A') ?: '—',
                'service' => $appt->service?->name,
                'status' => str_replace('_', ' ', $status),
                'can_act' => in_array($status, ['pending', 'confirmed'], true),
                'complete_url' => route('appointments.complete', $appt),
                'cancel_url' => route('appointments.cancel', $appt),
                'delete_url' => route('appointments.destroy', $appt),
            ];
        })->values()->all();

        return [
            'id' => $client->id,
            'name' => $client->name,
            'client_number' => $client->client_number,
            'phone' => $client->phone,
            'email' => $client->email,
            'notes' => $client->notes,
            'status' => $client->status?->value ?: 'inactive',
            'services' => $client->services->pluck('name')->values()->all(),
            'appointments_count' => (int) ($client->appointments_count ?? count($appointments)),
            'appointments' => $appointments,
            'deleteUrl' => route('clients.destroy', $client),
            'activeUrl' => route('clients.status', $client),
        ];
    })->keyBy('id');
@endphp

<div
    x-data
    x-init="
        const detailsId = @js(request('details'));
        if (!detailsId) return;
        const cards = @js($clientCards);
        const client = cards[detailsId] || cards[String(detailsId)];
        if (client) $nextTick(() => $store.clientUi.openDetails(client));
    "
    class="hidden"
    aria-hidden="true"
></div>

<div x-data="liveTableSearch(@js($filters['q'] ?? ''))">
    <x-legal.page-header title="Clients" subtitle="Customers who reached you by call — name, phone, services, and appointments">
        <x-slot:actions>
            @can('clients.manage')
                <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-client')">New client</button>
            @endcan
        </x-slot:actions>
    </x-legal.page-header>

    <div class="legal-toolbar">
        <div class="legal-toolbar-search">
            <input
                type="search"
                class="ui-field"
                placeholder="Search name, client number, email"
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
                        <th>Client no.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Service</th>
                        <th>Appointments</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                @forelse($clients as $client)
                    @php
                        $card = $clientCards[$client->id];
                        $search = strtolower(trim(implode(' ', array_filter([
                            $card['name'],
                            $card['client_number'],
                            $card['email'],
                            $card['phone'],
                            implode(' ', $card['services']),
                            $card['status'],
                        ]))));
                        $isActive = $card['status'] === 'active';
                    @endphp
                    <tr data-search="{{ $search }}">
                        <td class="fw-semibold">{{ $card['client_number'] ?: '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $card['name'] }}</div>
                            <div class="small ui-muted">{{ $card['phone'] ?: '—' }}</div>
                        </td>
                        <td>{{ $card['email'] ?: '—' }}</td>
                        <td>{{ implode(', ', $card['services']) ?: '—' }}</td>
                        <td>
                            <button type="button" class="ui-btn ui-btn-ghost" style="padding:0.35rem 0.65rem" @click="$store.clientUi.openDetails(@js($card))">
                                {{ $card['appointments_count'] }} appt{{ $card['appointments_count'] === 1 ? '' : 's' }}
                            </button>
                        </td>
                        <td>
                            <div class="client-actions">
                                <form method="POST" action="{{ route('clients.status', $client) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $isActive ? 'inactive' : 'active' }}">
                                    <button type="submit" class="lawyer-action {{ $isActive ? 'is-edit' : 'is-details' }}" style="width:auto;padding:0.4rem 0.6rem">
                                        {{ $isActive ? 'Inactive' : 'Active' }}
                                    </button>
                                </form>
                                <button type="button" class="lawyer-action is-details" style="width:auto;padding:0.4rem 0.6rem" @click="$store.clientUi.openDetails(@js($card))">
                                    Details
                                </button>
                                <button type="button" class="lawyer-action is-edit" style="width:auto;padding:0.4rem 0.6rem" @click="$store.clientUi.openEdit({{ $client->id }})">
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="lawyer-action is-delete"
                                    style="width:auto;padding:0.4rem 0.6rem"
                                    @click="$store.clientUi.askDelete({ name: @js($card['name']), url: @js($card['deleteUrl']) })"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="legal-empty">No clients yet.</td></tr>
                @endforelse
                    <tr x-ref="empty" hidden>
                        <td colspan="6" class="legal-empty">No matching clients.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3" x-ref="pager">{{ $clients->withQueryString()->links() }}</div>
    </x-legal.card>
</div>

@include('crm.clients._create-modal')
@include('crm.clients._details-modal')
@include('crm.clients._delete-modal')
@include('appointments._delete-modal')

@foreach($clients as $client)
    @include('crm.clients._edit-modal', ['client' => $client->loadMissing('services')])
@endforeach
@endsection

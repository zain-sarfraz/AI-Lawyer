@extends('layouts.app')

@section('content')
@php
    $lawyerCards = $lawyers->getCollection()->map(function ($lawyer) {
        $initials = collect(explode(' ', $lawyer->displayName()))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');

        return [
            'id' => $lawyer->id,
            'name' => $lawyer->displayName(),
            'title' => $lawyer->title ?: 'Counsel',
            'phone' => $lawyer->phone,
            'email' => $lawyer->user?->email,
            'experience' => $lawyer->experience,
            'timezone' => $lawyer->timezone,
            'bio' => $lawyer->bio,
            'status' => $lawyer->status?->value ?: 'inactive',
            'services' => $lawyer->services->pluck('name')->values()->all(),
            'initials' => strtoupper($initials),
            'deleteUrl' => route('lawyers.destroy', $lawyer),
        ];
    })->keyBy('id');
@endphp

<div
    x-data
    x-init="
        const detailsId = @js(request('details'));
        if (!detailsId) return;
        const cards = @js($lawyerCards);
        const lawyer = cards[detailsId] || cards[String(detailsId)];
        if (lawyer) $nextTick(() => $store.lawyerUi.openDetails(lawyer));
    "
    class="hidden"
    aria-hidden="true"
></div>

<x-legal.page-header title="Lawyers" subtitle="AI Lawyer counsel profiles used for calendar booking and voice routing">
    <x-slot:actions>
        <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-lawyer')">New lawyer</button>
    </x-slot:actions>
</x-legal.page-header>

@if($lawyers->isEmpty())
    <x-legal.card>
        <div class="legal-empty">
            <h3>No lawyers yet</h3>
            <p class="mb-3">Add counsel so appointments and the receptionist can assign the right lawyer.</p>
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-lawyer')">Add lawyer</button>
        </div>
    </x-legal.card>
@else
    <div class="lawyer-card-grid mb-3">
        @foreach($lawyers as $lawyer)
            @php
                $card = $lawyerCards[$lawyer->id];
                $isActive = $card['status'] === 'active';
                $services = collect($card['services']);
            @endphp
            <article class="lawyer-card">
                <div class="lawyer-card-top">
                    <div class="lawyer-card-identity">
                        <div class="lawyer-card-avatar">{{ $card['initials'] }}</div>
                        <div class="min-w-0">
                            <h3>{{ $card['name'] }}</h3>
                            <p>{{ $card['title'] }}</p>
                        </div>
                    </div>
                    <x-legal.badge :tone="$isActive ? 'success' : 'soft'">
                        {{ $card['status'] }}
                    </x-legal.badge>
                </div>

                <dl class="lawyer-card-meta">
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ $card['phone'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd class="truncate" title="{{ $card['email'] }}">{{ $card['email'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Experience</dt>
                        <dd>{{ $card['experience'] ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="lawyer-card-services">
                    @forelse($services->take(3) as $serviceName)
                        <span>{{ $serviceName }}</span>
                    @empty
                        <span class="is-empty">No services assigned</span>
                    @endforelse
                    @if($services->count() > 3)
                        <span class="is-more">+{{ $services->count() - 3 }} more</span>
                    @endif
                </div>

                <div class="lawyer-card-actions">
                    <a href="{{ route('schedule.availability', ['lawyer_id' => $lawyer->id]) }}" class="lawyer-action is-details">
                        <x-ui.icon name="clock" class="h-3.5 w-3.5" />
                        Availability
                    </a>
                    <button type="button" class="lawyer-action is-details" @click="$store.lawyerUi.openDetails(@js($card))">
                        <x-ui.icon name="eye" class="h-3.5 w-3.5" />
                        Details
                    </button>
                    <button type="button" class="lawyer-action is-edit" @click="$store.lawyerUi.openEdit({{ $lawyer->id }})">
                        <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                        Edit
                    </button>
                    <button
                        type="button"
                        class="lawyer-action is-delete"
                        @click="$store.lawyerUi.askDelete({ id: {{ $lawyer->id }}, name: @js($card['name']), url: @js($card['deleteUrl']) })"
                    >
                        <x-ui.icon name="trash" class="h-3.5 w-3.5" />
                        Delete
                    </button>
                </div>
            </article>
        @endforeach
    </div>
    {{ $lawyers->links() }}
@endif

@include('admin.lawyers._create-modal')
@include('admin.lawyers._details-modal')
@include('admin.lawyers._delete-modal')

@foreach($lawyers as $lawyer)
    @include('admin.lawyers._edit-modal', ['lawyer' => $lawyer->loadMissing('user', 'services')])
@endforeach
@endsection

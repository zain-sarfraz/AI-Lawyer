@extends('layouts.app')

@section('title', 'Users')

@section('content')
@php
    $userCards = $users->getCollection()->map(function ($user) {
        $initials = collect(explode(' ', $user->name ?: 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
        $role = $user->getRoleNames()->first() ?: 'Staff';
        $isLawyer = $user->hasRole('Lawyer');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'title' => $isLawyer ? ($user->lawyer?->title ?: 'Lawyer') : 'Staff',
            'phone' => $user->lawyer?->phone,
            'email' => $user->email,
            'role' => $role,
            'status' => $user->status?->value ?: 'inactive',
            'initials' => strtoupper($initials),
            'isLawyer' => $isLawyer,
            'deleteUrl' => route('users.destroy', $user),
            'statusUrl' => route('users.status', $user),
            'toggleStatus' => ($user->status?->value ?? 'active') === 'active' ? 'inactive' : 'active',
        ];
    })->keyBy('id');
@endphp

<div
    x-data
    x-init="
        const detailsId = @js(request('details'));
        if (!detailsId) return;
        const cards = @js($userCards);
        const user = cards[detailsId] || cards[String(detailsId)];
        if (user) $nextTick(() => $store.userUi.openDetails(user));
    "
    class="hidden"
    aria-hidden="true"
></div>

<x-legal.page-header title="Users" subtitle="Lawyers and staff with their own dashboards, appointments, and records">
    <x-slot:actions>
        <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-user')">Add user</button>
    </x-slot:actions>
</x-legal.page-header>

@if($users->isEmpty())
    <x-legal.card>
        <div class="legal-empty">
            <h3>No users yet</h3>
            <p class="mb-3">Add a lawyer or staff member to get started.</p>
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-user')">Add user</button>
        </div>
    </x-legal.card>
@else
    <div class="lawyer-card-grid mb-3">
        @foreach($users as $user)
            @php
                $card = $userCards[$user->id];
                $isActive = $card['status'] === 'active';
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
                        <dt>Role</dt>
                        <dd>{{ $card['role'] }}</dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ $card['phone'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd class="truncate" title="{{ $card['email'] }}">{{ $card['email'] ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="lawyer-card-services">
                    <span>{{ $card['role'] }}</span>
                    @if($card['isLawyer'])
                        <span>Calendar booking</span>
                    @else
                        <span>Front desk</span>
                    @endif
                </div>

                <div class="lawyer-card-actions">
                    <button type="button" class="lawyer-action is-details" @click="$store.userUi.openDetails(@js($card))">
                        <x-ui.icon name="eye" class="h-3.5 w-3.5" />
                        Details
                    </button>
                    <button type="button" class="lawyer-action is-edit" @click="$store.userUi.openEdit({{ $user->id }})">
                        <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                        Edit
                    </button>
                    <button
                        type="button"
                        class="lawyer-action is-delete"
                        @click="$store.userUi.askDelete({ id: {{ $user->id }}, name: @js($card['name']), url: @js($card['deleteUrl']), isLawyer: {{ $card['isLawyer'] ? 'true' : 'false' }} })"
                    >
                        <x-ui.icon name="trash" class="h-3.5 w-3.5" />
                        Delete
                    </button>
                </div>
            </article>
        @endforeach
    </div>
    {{ $users->links() }}
@endif

@include('admin.users._create-modal')
@include('admin.users._details-modal')
@include('admin.users._delete-modal')

@foreach($users as $user)
    @include('admin.users._edit-modal', ['user' => $user])
@endforeach
@endsection

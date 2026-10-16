@extends('layouts.app')

@section('content')
@php
    $initials = collect(explode(' ', $lawyer->displayName()))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $isActive = ($lawyer->status?->value ?? '') === 'active';
@endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <a href="{{ route('lawyers.index') }}" class="small ui-muted">← Back to lawyers</a>
        <h1 class="font-display text-3xl leading-tight mt-1">{{ $lawyer->displayName() }}</h1>
        <p class="mt-1 text-sm text-(--muted)">{{ $lawyer->title ?: 'Counsel' }} · Lawyer profile</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('lawyers.edit', $lawyer) }}" class="ui-btn ui-btn-primary">Edit</a>
        <form method="POST" action="{{ route('lawyers.destroy', $lawyer) }}" onsubmit="return confirm('Delete this lawyer?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="ui-btn ui-btn-ghost text-rose-600">Delete</button>
        </form>
    </div>
</div>

<div class="lawyer-detail-grid">
    <x-legal.card>
        <div class="lawyer-card-identity mb-4">
            <div class="lawyer-card-avatar is-lg">{{ strtoupper($initials) }}</div>
            <div>
                <h2 class="font-display text-xl m-0">{{ $lawyer->displayName() }}</h2>
                <p class="ui-muted mb-2">{{ $lawyer->title ?: 'Counsel' }}</p>
                <x-legal.badge :tone="$isActive ? 'success' : 'soft'">
                    {{ $lawyer->status?->value ?: 'inactive' }}
                </x-legal.badge>
            </div>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Phone</dt>
                <dd>{{ $lawyer->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $lawyer->user?->email ?: '—' }}</dd>
            </div>
            <div>
                <dt>Experience</dt>
                <dd>{{ $lawyer->experience ?: '—' }}</dd>
            </div>
            <div>
                <dt>Timezone</dt>
                <dd>{{ $lawyer->timezone ?: '—' }}</dd>
            </div>
        </dl>
    </x-legal.card>

    <x-legal.card>
        <h3 class="font-display text-lg mb-2">Services</h3>
        <div class="lawyer-card-services">
            @forelse($lawyer->services as $service)
                <span>{{ $service->name }}</span>
            @empty
                <span class="is-empty">No services assigned</span>
            @endforelse
        </div>

        <h3 class="font-display text-lg mb-2 mt-4">Bio / notes</h3>
        <p class="ui-muted mb-0" style="white-space: pre-wrap">{{ $lawyer->bio ?: 'No bio added yet.' }}</p>
    </x-legal.card>
</div>
@endsection

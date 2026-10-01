@extends('layouts.app')

@section('actions')
<a href="{{ route('lawyers.create') }}" class="btn btn-legal-primary">New lawyer</a>
@endsection

@section('content')
<x-legal.page-header title="Lawyers" subtitle="Counsel profiles, practice areas, and availability" />

@if($lawyers->isEmpty())
    <x-legal.card>
        <div class="legal-empty">
            <h3>No lawyers yet</h3>
            <p class="mb-3">Add counsel so the AI receptionist can book against verified availability.</p>
            <a href="{{ route('lawyers.create') }}" class="btn btn-legal-primary">Add lawyer</a>
        </div>
    </x-legal.card>
@else
    <div class="legal-profile-grid mb-3">
        @foreach($lawyers as $lawyer)
            @php
                $initials = collect(explode(' ', $lawyer->displayName()))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
            @endphp
            <div class="legal-profile-card">
                <div class="legal-profile-card-head">
                    <div class="legal-profile-avatar">{{ strtoupper($initials) }}</div>
                    <div>
                        <h3>{{ $lawyer->displayName() }}</h3>
                        <div class="role">{{ $lawyer->title ?: 'Counsel' }}</div>
                    </div>
                </div>
                <div class="legal-profile-meta mb-3">
                    <div><strong>Status:</strong> {{ $lawyer->status?->value }}</div>
                    <div><strong>Services:</strong> {{ $lawyer->services->pluck('name')->join(', ') ?: '—' }}</div>
                    <div><strong>Timezone:</strong> {{ $lawyer->timezone ?: 'Firm default' }}</div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <x-legal.badge :tone="$lawyer->status?->value === 'active' ? 'success' : 'soft'">
                        {{ $lawyer->status?->value }}
                    </x-legal.badge>
                    <a href="{{ route('lawyers.edit', $lawyer) }}">Edit</a>
                </div>
            </div>
        @endforeach
    </div>
    {{ $lawyers->links() }}
@endif
@endsection

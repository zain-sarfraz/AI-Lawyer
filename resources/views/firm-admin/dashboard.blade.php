@extends('layouts.app')

@section('title', 'Firm Admin Dashboard')

@section('content')
@php
    $statusTone = fn ($status) => match ($status) {
        'active' => 'success',
        'suspended' => 'danger',
        default => 'soft',
    };
@endphp

<section class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-600 dark:text-teal-300">Firm administration</p>
        <h1 class="font-display mt-1 text-[clamp(1.7rem,3vw,2.25rem)] font-bold tracking-tight text-slate-900 dark:text-white">{{ $greeting }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Managing <strong class="text-slate-800 dark:text-slate-100">{{ $firm->firm_name }}</strong>
            · {{ ucfirst((string) ($firm->plan ?: 'custom')) }} plan
            · {{ $firm->email ?: 'No firm email set' }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('settings.edit') }}" class="ui-btn ui-btn-secondary">Firm profile</a>
        @if ($canAddLawyer)
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'add-lawyer')">
                <x-ui.icon name="user-plus" class="h-4 w-4" />
                Add Lawyer
            </button>
        @endif
    </div>
</section>

<div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-legal.kpi label="Total Lawyers" :value="$stats['total']" hint="Registered under this firm" tone="mint" icon="users" />
    <x-legal.kpi label="Active Lawyers" :value="$stats['active']" hint="Currently active accounts" tone="blue" icon="user-plus" />
    <x-legal.kpi label="With Phone" :value="$stats['with_phone']" hint="Profiles that include a phone number" tone="violet" icon="phone" />
    <x-legal.kpi label="Inactive Lawyers" :value="$stats['inactive']" hint="Deactivated or suspended" tone="amber" icon="shield" />
</div>

<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lawyers management</h2>
        <p class="text-sm text-slate-500">Search, filter, and manage every lawyer in this workspace.</p>
    </div>
    <a href="{{ route('firm.lawyers.index') }}" class="text-sm font-semibold text-teal-600 hover:text-teal-500">Open full lawyers page →</a>
</div>

<form method="GET" action="{{ route('firm.dashboard') }}" class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-navy-900/40 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <label class="sr-only" for="q">Search</label>
        <input id="q" name="q" value="{{ $filters['q'] }}" type="search" placeholder="Search by name, email, or phone" class="ui-field w-full">
    </div>
    <div>
        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status" class="ui-field w-full">
            <option value="">All statuses</option>
            <option value="active" @selected($filters['status'] === 'active')>Active</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
            <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
        </select>
    </div>
    <div class="flex gap-2">
        <select name="sort" class="ui-field w-full">
            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
            <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest</option>
            <option value="name" @selected($filters['sort'] === 'name')>Name</option>
            <option value="email" @selected($filters['sort'] === 'email')>Email</option>
        </select>
        <button type="submit" class="ui-btn ui-btn-secondary shrink-0">Filter</button>
    </div>
</form>

<x-legal.card>
    @if ($lawyers->isEmpty())
        <div class="legal-empty py-10 text-center">
            <h3 class="text-lg font-semibold">No lawyers found</h3>
            <p class="mb-4 text-sm text-slate-500">Add your first firm lawyer to start building the workspace roster.</p>
            @if ($canAddLawyer)
                <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'add-lawyer')">+ Add Lawyer</button>
            @endif
        </div>
    @else
        <div class="ui-table-wrap overflow-x-auto">
            <table class="ui-table firm-lawyers-table w-full min-w-[860px] text-left text-sm">
                <thead>
                    <tr>
                        <th>Lawyer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Date added</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lawyers as $member)
                        @php
                            $status = $member->status?->value ?? 'inactive';
                            $phone = $member->lawyer?->phone;
                            $initials = strtoupper(collect(explode(' ', $member->name ?: 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
                        @endphp
                        <tr>
                            <td>
                                <div class="firm-lawyer-identity">
                                    <span class="firm-lawyer-avatar">{{ $initials }}</span>
                                    <div class="min-w-0">
                                        <div class="firm-lawyer-name">{{ $member->name }}</div>
                                        <div class="firm-lawyer-meta">{{ $member->getRoleNames()->first() ?: 'Lawyer' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="firm-lawyer-email">{{ $member->email }}</td>
                            <td>
                                @if ($phone)
                                    <a class="firm-lawyer-phone" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
                                @else
                                    <span class="firm-lawyer-phone is-empty">No phone</span>
                                @endif
                            </td>
                            <td><x-legal.badge :tone="$statusTone($status)">{{ $status }}</x-legal.badge></td>
                            <td class="firm-lawyer-date">{{ optional($member->created_at)->format('M j, Y') }}</td>
                            <td class="text-right">
                                @include('firm-admin.lawyers._actions', ['member' => $member])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $lawyers->links() }}</div>
    @endif
</x-legal.card>

@include('firm-admin.lawyers._modals')
@endsection

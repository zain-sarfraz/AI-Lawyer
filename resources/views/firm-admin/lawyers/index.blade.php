@extends('layouts.app')

@section('title', 'Lawyers')

@section('content')
@php
    $statusTone = fn ($status) => match ($status) {
        'active' => 'success',
        'suspended' => 'danger',
        default => 'soft',
    };
@endphp

<x-legal.page-header title="Lawyers" subtitle="Add and manage lawyers in this firm workspace">
    <x-slot:actions>
        @if ($canAddLawyer)
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'add-lawyer')">
                <x-ui.icon name="user-plus" class="h-4 w-4" />
                Add Lawyer
            </button>
        @endif
    </x-slot:actions>
</x-legal.page-header>

<form method="GET" action="{{ route('firm.lawyers.index') }}" class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-navy-900/40 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="Search by name, email, or phone" class="ui-field w-full">
    </div>
    <div>
        <select name="status" class="ui-field w-full">
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
            <h3 class="text-lg font-semibold">No lawyers yet</h3>
            <p class="mb-4 text-sm text-slate-500">Add a lawyer to this firm. They will appear here immediately and can sign in.</p>
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
                        <th>Country &amp; Cases</th>
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
                                        <div class="firm-lawyer-meta">{{ $member->getRoleNames()->first() ?: 'Lawyer' }}@if($member->lawyer?->title) · {{ $member->lawyer->title }}@endif</div>
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
                            <td>
                                @php
                                    $cCode = strtoupper((string) ($member->lawyer?->country ?? ''));
                                    $cDial = $cCode ? \App\Support\Phone\PhoneNormalizer::dialCode($cCode) : '';
                                    $jurs = $member->lawyer?->jurisdictions ?? collect();
                                @endphp
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $cCode ? ($cCode . ($cDial ? ' (' . $cDial . ')' : '')) : 'Not set' }}
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse ($jurs->take(3) as $jur)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10.5px] font-medium text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $jur->code }}</span>
                                    @empty
                                        <span class="text-[11px] text-slate-400">No cases assigned</span>
                                    @endforelse
                                    @if ($jurs->count() > 3)
                                        <span class="text-[11px] text-slate-500">+{{ $jurs->count() - 3 }}</span>
                                    @endif
                                </div>
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

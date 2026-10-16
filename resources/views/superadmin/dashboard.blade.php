@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-[clamp(1.75rem,3vw,2.2rem)] font-extrabold tracking-tight">Dashboard</h1>
    <p class="mt-1 text-(--muted)">Platform overview. Manage firms and admins from their dedicated pages.</p>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <x-legal.kpi label="Firms" :value="$stats['firms']" hint="Total workspaces" tone="mint" icon="briefcase" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Active firms" :value="$stats['active_firms']" hint="Can log in" tone="blue" icon="grid" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Suspended" :value="$stats['suspended_firms']" hint="Blocked workspaces" tone="violet" icon="gear" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Firm admins" :value="$stats['firm_admins']" hint="Owner / Admin users" tone="teal" icon="users" />
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <h2 class="legal-card-title mb-0">Recent firms</h2>
</div>

<x-legal.card>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-(--line) text-left text-(--muted)">
                    <th class="py-2 pr-3">Firm</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3">Timezone</th>
                    <th class="py-2">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentFirms as $firm)
                    <tr class="border-b border-(--line)/60">
                        <td class="py-3 pr-3">
                            <a class="font-semibold text-(--ink)" href="{{ route('superadmin.firms.show', $firm) }}">{{ $firm->firm_name }}</a>
                        </td>
                        <td class="py-3 pr-3"><span class="voice-pill">{{ $firm->status ?? 'active' }}</span></td>
                        <td class="py-3 pr-3 text-(--muted)">{{ $firm->timezone }}</td>
                        <td class="py-3 text-(--muted)">{{ optional($firm->created_at)->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-(--muted)">No firms yet. Use Firm to create a workspace.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-legal.card>
@endsection

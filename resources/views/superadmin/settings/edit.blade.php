@extends('layouts.superadmin')

@section('title', 'Setting')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Setting</h1>
    <p class="mt-1 text-(--muted)">Platform configuration overview. Firm CRM settings stay inside each firm workspace.</p>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <x-legal.kpi label="Firms" :value="$stats['firms']" hint="Total workspaces" tone="mint" icon="briefcase" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Active firms" :value="$stats['active_firms']" hint="Can sign in" tone="blue" icon="grid" />
    </div>
    <div class="col-6 col-xl">
        <x-legal.kpi label="Firm admins" :value="$stats['admins']" hint="Owner / Admin" tone="teal" icon="users" />
    </div>
</div>

<!-- <x-legal.card>
    <h2 class="legal-card-title">Platform</h2>
    <div class="mt-3 small space-y-2">
        <div><strong>App name:</strong> {{ $appName }}</div>
        <div><strong>App URL:</strong> {{ $appUrl }}</div>
        <div><strong>Timezone:</strong> {{ $timezone }}</div>
    </div>
    <p class="mt-4 text-xs text-(--muted)">These values come from <code>.env</code>. Change them there and clear config cache if needed.</p>
</x-legal.card> -->
@endsection

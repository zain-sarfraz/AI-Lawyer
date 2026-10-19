<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Lawyer') }} — @yield('title', 'Platform')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased" x-data="{ mobileOpen: false }">
@php
    $user = auth()->user();
    $nav = [
        ['route' => 'superadmin.dashboard', 'match' => 'superadmin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
        ['route' => 'superadmin.firms.index', 'match' => 'superadmin.firms.*', 'label' => 'Firm', 'icon' => 'briefcase'],
        ['route' => 'superadmin.admins.index', 'match' => 'superadmin.admins.*', 'label' => 'Admins', 'icon' => 'users'],
        ['route' => 'superadmin.settings.edit', 'match' => 'superadmin.settings.*', 'label' => 'Setting', 'icon' => 'gear'],
        ['route' => 'superadmin.profile.edit', 'match' => 'superadmin.profile.*', 'label' => 'Profile', 'icon' => 'shield'],
    ];
@endphp

<div class="flex min-h-screen w-full overflow-x-clip">
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-30 bg-navy-950/50 lg:hidden" @click="mobileOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-white/5 bg-navy-900 text-slate-200 transition lg:sticky lg:top-0 lg:h-screen"
           :class="mobileOpen ? 'translate-x-0' : '-translate-x-full max-lg:invisible lg:visible lg:translate-x-0'">
        <div class="flex items-center gap-3 px-4 py-5">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-sm font-extrabold text-white shadow-[0_0_0_3px_rgba(18,138,130,0.25)]">SA</div>
            <div>
                <div class="font-display text-lg font-semibold leading-none tracking-tight text-white">Superadmin</div>
                <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-gold-400">AI Lawyer</div>
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 pb-5">
            @foreach($nav as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-teal-500/15 text-teal-300 shadow-[inset_3px_0_0_0_#128a82]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon :name="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/5 px-3 py-4">
            <div class="mb-2 px-2 text-xs text-slate-400">{{ $user?->email }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-200 hover:bg-white/5" type="submit">Log out</button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <button type="button" class="fixed left-3 top-3 z-20 grid h-10 w-10 place-items-center rounded-xl border border-(--line) bg-(--surface) lg:hidden" @click="mobileOpen = true" aria-label="Open menu">
            <x-ui.icon name="chevron-right" />
        </button>

        <main class="app-main mx-auto w-full max-w-7xl min-w-0 flex-1 px-4 py-6 pt-14 sm:px-6 lg:pt-6">
            <!-- <div class="mb-4 rounded-xl border border-(--line) bg-(--surface) px-4 py-3 text-sm text-(--muted) shadow-sm">
                <span class="font-semibold text-(--ink)">Platform portal.</span>
                Firm CRM data (leads, clients, appointments, calls) stays inside each firm workspace.
            </div> -->

            @if(session('success'))
                <x-ui.alert class="mb-4">{{ session('success') }}</x-ui.alert>
            @endif
            @if(isset($errors) && $errors->any())
                <x-ui.alert tone="danger" class="mb-4">
                    <ul class="list-disc pl-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@php
    $modalFirms = \App\Models\Firm::query()
        ->orderBy('firm_name')
        ->get(['id', 'firm_name', 'status']);
    $modalFirmsActive = $modalFirms->where('status', 'active')->values();
@endphp
@include('superadmin.partials.create-firm-modal')
@include('superadmin.partials.edit-firm-modal')
@include('superadmin.partials.add-admin-modal', [
    'modalFirms' => $modalFirmsActive,
    'selectedFirmId' => old('firm_id', request('firm_id')),
])
@include('superadmin.partials.edit-admin-modal', [
    'modalFirms' => $modalFirms,
])
@include('superadmin.partials.delete-admin-modal')
@include('superadmin.partials.delete-firm-modal')
@stack('scripts')
</body>
</html>

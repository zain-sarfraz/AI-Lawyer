<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LEGALAI') }} — @yield('title', 'Workspace')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased" x-data="shell()" @keydown.window.meta.k.prevent="openPalette()" @keydown.window.ctrl.k.prevent="openPalette()" @keydown.escape.window="palette = false; mobileOpen = false">
@php
    $firmName = optional(\App\Models\FirmSetting::query()->first())->firm_name ?: 'Law Firm';
    $nav = [
        ['group' => 'Workspace', 'items' => [
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
            ['route' => 'ai.show', 'match' => 'ai.*', 'label' => 'Voice line', 'icon' => 'broadcast'],
            ['route' => 'calls.index', 'match' => 'calls.*', 'label' => 'Calls', 'icon' => 'phone'],
            ['route' => 'appointments.index', 'match' => 'appointments.*', 'label' => 'Appointments', 'icon' => 'calendar-check'],
            ['route' => 'schedule.index', 'match' => 'schedule.*', 'label' => 'Calendar', 'icon' => 'calendar'],
        ]],
        ['group' => 'CRM', 'items' => [
            ['route' => 'leads.index', 'match' => 'leads.*', 'label' => 'Leads', 'icon' => 'user-plus'],
            ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clients', 'icon' => 'users'],
        ]],
        ['group' => 'Firm', 'items' => [
            ['route' => 'lawyers.index', 'match' => 'lawyers.*', 'label' => 'Lawyers', 'icon' => 'briefcase'],
            ['route' => 'services.index', 'match' => 'services.*', 'label' => 'Services', 'icon' => 'journal'],
            ['route' => 'knowledge.index', 'match' => 'knowledge.*', 'label' => 'Knowledge Base', 'icon' => 'book'],
        ]],
        ['group' => 'Insights', 'items' => [
            ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Reports', 'icon' => 'chart'],
        ]],
        ['group' => 'System', 'items' => [
            ['route' => 'settings.edit', 'match' => 'settings.*', 'label' => 'Settings', 'icon' => 'gear'],
            ['route' => 'audit.index', 'match' => 'audit.*', 'label' => 'Audit Logs', 'icon' => 'shield'],
        ]],
    ];
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name ?? 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $roleLabel = $user->role->label ?? $user->role->name ?? 'Team';
    $gatewayConfigured = filled(config('services.voice_gateway.public_ws_url')) || filled(config('services.openai.api_key'));
    $paletteItems = collect($nav)->flatMap(fn ($section) => collect($section['items'])->map(fn ($item) => [
        'label' => $item['label'],
        'group' => $section['group'],
        'href' => route($item['route']),
    ]))->values();
@endphp

<div class="flex min-h-screen">
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-30 bg-navy-950/50 lg:hidden" @click="mobileOpen = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-white/5 bg-navy-950 text-stone-200 transition-all duration-200 lg:sticky lg:top-0 lg:h-screen"
        :class="[collapsed ? 'w-[4.75rem]' : 'w-64', mobileOpen ? 'translate-x-0' : '-translate-x-full max-lg:invisible lg:visible lg:translate-x-0']"
    >
        <div class="flex items-center gap-3 px-4 py-5">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gold-500 text-sm font-bold text-navy-950">LA</div>
            <div x-show="!collapsed" x-cloak>
                <div class="font-display text-lg leading-none text-stone-50">LEGALAI</div>
                <div class="mt-1 text-[10px] uppercase tracking-[0.16em] text-gold-400">Voice operations</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-3 pb-6">
            @foreach($nav as $section)
                <div x-show="!collapsed" x-cloak class="mb-2 mt-4 px-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-500">{{ $section['group'] }}</div>
                <nav class="space-y-1">
                    @foreach($section['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           title="{{ $item['label'] }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs($item['match']) ? 'bg-white/10 text-gold-400' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                            <x-ui.icon :name="$item['icon']" />
                            <span x-show="!collapsed" x-cloak>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-(--line) bg-(--surface)/90 px-4 py-3 backdrop-blur">
            <div class="flex min-w-0 items-center gap-2">
                <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-(--line) lg:hidden" @click="mobileOpen = true" aria-label="Open menu">
                    <x-ui.icon name="menu" />
                </button>
                <button type="button" class="hidden h-9 w-9 place-items-center rounded-xl border border-(--line) lg:grid" @click="toggleCollapsed()" aria-label="Collapse sidebar">
                    <x-ui.icon name="menu" />
                </button>
                <div class="hidden truncate text-sm font-semibold md:block">{{ $firmName }}</div>
                <button type="button" class="hidden items-center gap-2 rounded-full border border-(--line) bg-(--canvas) px-3 py-1.5 text-sm text-(--muted) lg:inline-flex" @click="openPalette()">
                    <x-ui.icon name="search" />
                    <span>Search the desk</span>
                    <kbd class="rounded bg-white/70 px-1.5 text-[10px] dark:bg-white/10">Ctrl K</kbd>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="hidden items-center gap-2 md:flex">
                    @yield('actions')
                </div>
                <x-legal.ai-status :online="$gatewayConfigured" class="!border-(--line) !bg-navy-900" />
                <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-(--line)" @click="toggleDark()" aria-label="Toggle color mode">
                    <span x-show="!dark"><x-ui.icon name="moon" /></span>
                    <span x-show="dark" x-cloak><x-ui.icon name="sun" /></span>
                </button>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="flex items-center gap-2 rounded-full border border-(--line) py-1 pl-1 pr-3" @click="open = !open">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-navy-900 text-xs font-semibold text-gold-400">{{ strtoupper($initials) }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm font-semibold leading-tight">{{ $user->name }}</span>
                            <span class="block text-[11px] text-(--muted)">{{ $roleLabel }}</span>
                        </span>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-2xl border border-(--line) bg-(--surface) p-2 shadow-xl">
                        <a class="block rounded-xl px-3 py-2 text-sm hover:bg-(--canvas)" href="{{ route('profile.edit') }}">Profile</a>
                        <a class="block rounded-xl px-3 py-2 text-sm hover:bg-(--canvas)" href="{{ route('settings.edit') }}">Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-(--canvas)" type="submit">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">
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

            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>
</div>

<div x-show="palette" x-cloak class="fixed inset-0 z-50 flex justify-center bg-navy-950/50 px-4 pt-[12vh]" @click.self="palette = false">
    <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-(--line) bg-(--surface) shadow-2xl" @click.stop>
        <div class="flex items-center gap-2 border-b border-(--line) px-4">
            <x-ui.icon name="search" />
            <input x-ref="paletteQuery" x-model="query" type="search" placeholder="Jump to a page" class="w-full border-0 bg-transparent py-3 text-sm focus:ring-0" aria-label="Command palette">
        </div>
        <ul class="max-h-80 overflow-y-auto p-2">
            @foreach($paletteItems as $item)
                <li x-show="!query || $el.dataset.label.includes(query.toLowerCase())" data-label="{{ strtolower($item['label'].' '.$item['group']) }}">
                    <a href="{{ $item['href'] }}" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-(--canvas)">
                        <span>{{ $item['label'] }}</span>
                        <span class="text-xs text-(--muted)">{{ $item['group'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

@stack('scripts')
</body>
</html>

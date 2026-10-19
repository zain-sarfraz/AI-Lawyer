<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Lawyer') }} — @yield('title', 'Workspace')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased" x-data="shell()" @keydown.escape.window="mobileOpen = false">
@php
    $user = auth()->user();
    $allows = function (?string $permission) use ($user): bool {
        if ($permission === null) {
            return true;
        }

        if (str_contains($permission, '|')) {
            foreach (explode('|', $permission) as $name) {
                if ($user?->can($name)) {
                    return true;
                }
            }

            return false;
        }

        return (bool) $user?->can($permission);
    };
    $nav = [
        ['group' => 'Workspace', 'items' => [
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'permission' => null],
            ['route' => 'schedule.index', 'match' => 'schedule.index', 'label' => 'Calendar', 'icon' => 'calendar', 'permission' => 'schedules.manage|appointments.view'],
            ['route' => 'schedule.availability', 'match' => 'schedule.availability*', 'label' => 'Availability', 'icon' => 'clock', 'permission' => 'schedules.manage'],
            ['route' => 'appointments.index', 'match' => 'appointments.*', 'label' => 'Appointments', 'icon' => 'calendar-check', 'permission' => 'appointments.view'],
        ]],
        ['group' => 'Firm', 'items' => [
            ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clients', 'icon' => 'users', 'permission' => 'clients.view'],
            ['route' => 'cases.index', 'match' => 'cases.*', 'label' => 'Cases Management', 'icon' => 'scales', 'permission' => 'cases.view'],
            ['route' => 'lawyers.index', 'match' => 'lawyers.*', 'label' => 'Lawyers', 'icon' => 'briefcase', 'permission' => 'lawyers.manage'],
            ['route' => 'services.index', 'match' => 'services.*', 'label' => 'Services', 'icon' => 'journal', 'permission' => 'services.manage'],
            ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Reports', 'icon' => 'chart', 'permission' => 'reports.view'],
            ['route' => 'ai.show', 'match' => 'ai.*', 'label' => 'Agent Setting', 'icon' => 'mic', 'permission' => 'ai.settings.manage'],
            ['route' => 'settings.edit', 'match' => 'settings.*', 'label' => 'Settings', 'icon' => 'gear', 'permission' => 'firm.settings.manage'],
        ]],
    ];
    $isRestrictedLawyer = $user && $user->hasRole('Lawyer') && ! $user->hasAnyRole(['Owner', 'Admin']);
    $nav = collect($nav)->map(function (array $section) use ($allows, $isRestrictedLawyer) {
        $section['items'] = collect($section['items'])
            ->filter(function (array $item) use ($allows, $isRestrictedLawyer) {
                if ($isRestrictedLawyer && in_array($item['route'], ['clients.index', 'lawyers.index', 'services.index', 'ai.show', 'settings.edit'], true)) {
                    return false;
                }

                return $allows($item['permission']);
            })
            ->values()
            ->all();

        return $section;
    })->filter(fn (array $section) => count($section['items']) > 0)->values()->all();
    $initials = collect(explode(' ', $user->name ?? 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $roleLabel = $user?->getRoleNames()->first() ?: 'Team';
@endphp

<div class="flex min-h-screen w-full overflow-x-clip">
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-30 bg-navy-950/50 lg:hidden" @click="mobileOpen = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-white/5 bg-navy-900 text-slate-200 transition-all duration-200 lg:sticky lg:top-0 lg:h-screen"
        :class="[collapsed ? 'w-[4.75rem]' : 'w-64', mobileOpen ? 'translate-x-0' : '-translate-x-full max-lg:invisible lg:visible lg:translate-x-0']"
    >
        <div class="relative flex items-center gap-3 px-4 py-5">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-sm font-extrabold text-white shadow-[0_0_0_3px_rgba(18,138,130,0.28)]">AL</div>
            <div x-show="!collapsed" x-cloak class="min-w-0 flex-1">
                <div class="font-display text-lg font-semibold leading-none tracking-tight text-white">AI Lawyer</div>
                <div class="mt-1 text-[10px] uppercase tracking-[0.14em] text-gold-400">Legal workspace</div>
            </div>
            <button
                type="button"
                class="absolute -right-3 top-1/2 z-50 hidden h-7 w-7 -translate-y-1/2 place-items-center rounded-full border border-white/10 bg-navy-800 text-teal-300 shadow-lg transition hover:bg-teal-500 hover:text-navy-950 lg:grid"
                @click="toggleCollapsed()"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <span x-show="!collapsed"><x-ui.icon name="chevron-left" class="h-4 w-4" /></span>
                <span x-show="collapsed" x-cloak><x-ui.icon name="chevron-right" class="h-4 w-4" /></span>
            </button>
            <button
                type="button"
                class="ml-auto grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-white/5 hover:text-white lg:hidden"
                @click="mobileOpen = false"
                aria-label="Close menu"
            >
                <x-ui.icon name="chevron-left" class="h-4 w-4" />
            </button>
        </div>

        <div class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-5">
            @foreach($nav as $section)
                <div x-show="!collapsed" x-cloak class="mb-2 mt-4 px-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $section['group'] }}</div>
                <nav class="space-y-1">
                    @foreach($section['items'] as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           title="{{ $item['label'] }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-teal-500/20 text-teal-300 shadow-[inset_0_0_0_1px_rgba(45,212,191,0.25)]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <x-ui.icon :name="$item['icon']" class="{{ $active ? 'text-teal-300' : '' }}" />
                            <span x-show="!collapsed" x-cloak>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </div>

        <div class="mt-auto border-t border-white/5 px-3 py-4">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="flex w-full items-center gap-2 rounded-xl border border-white/10 py-1.5 pl-1.5 pr-2 text-left hover:bg-white/5" @click="open = !open" :class="collapsed ? 'justify-center px-1.5' : ''">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal-500/20 text-xs font-bold text-teal-300">{{ strtoupper($initials) }}</span>
                    <span x-show="!collapsed" x-cloak class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold leading-tight text-white">{{ $user->name }}</span>
                        <span class="block text-[11px] text-slate-400">{{ $roleLabel }}</span>
                    </span>
                </button>
                <div x-show="open" x-cloak class="absolute bottom-full left-0 z-50 mb-2 w-56 rounded-2xl border border-white/10 bg-navy-800 p-2 shadow-xl" :class="collapsed ? 'left-full top-auto bottom-0 mb-0 ml-2' : ''">
                    <a class="block rounded-xl px-3 py-2 text-sm text-slate-200 hover:bg-white/5" href="{{ route('profile.edit') }}">Profile</a>
                    @can('firm.settings.manage')
                        <a class="block rounded-xl px-3 py-2 text-sm text-slate-200 hover:bg-white/5" href="{{ route('settings.edit') }}">Settings</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-200 hover:bg-white/5" type="submit">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <!-- Top Sticky Header Bar with Bell Notification & Sound -->
        <header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-white/5 bg-navy-900/90 px-4 sm:px-6 backdrop-blur-md">
            <!-- Left: Mobile toggle & Firm indicator -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-300 shadow-sm transition hover:bg-white/10 hover:text-white lg:hidden"
                    @click="mobileOpen = true"
                    aria-label="Open menu"
                >
                    <x-ui.icon name="chevron-right" />
                </button>

                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                    </span>
                    <span class="hidden sm:inline text-xs font-bold uppercase tracking-wider text-teal-400">
                        {{ $user->firm?->firm_name ?? 'Firm Desk' }}
                    </span>
                    <span class="hidden md:inline rounded-full bg-white/5 px-2 py-0.5 text-[10px] font-semibold text-slate-400">
                        {{ $roleLabel }}
                    </span>
                </div>
            </div>

            <!-- Right: Search trigger, Theme toggle, and Notification Bell -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    @click="toggleDark()"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-white"
                    title="Toggle Theme"
                >
                    <span x-show="dark" x-cloak><x-ui.icon name="sun" class="h-4 w-4 text-amber-300" /></span>
                    <span x-show="!dark"><x-ui.icon name="moon" class="h-4 w-4 text-slate-300" /></span>
                </button>

                <!-- Notification Bell Component with Blinking & Sound -->
                <x-navbar-appointment-notifications />
            </div>
        </header>

        <main class="app-main mx-auto w-full max-w-7xl min-w-0 flex-1 px-4 py-6 sm:px-6">
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

@stack('scripts')
</body>
</html>

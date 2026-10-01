<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LEGALAI') }} — @yield('title', 'CRM')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/crm.css') }}?v=legal6" rel="stylesheet">
</head>
<body class="legal-body">
@php
    $firmName = optional(\App\Models\FirmSetting::query()->first())->firm_name ?: 'Law Firm';
    $nav = [
        ['group' => 'Workspace', 'items' => [
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid'],
            ['route' => 'ai.show', 'match' => 'ai.*', 'label' => 'Voice line', 'icon' => 'bi-broadcast'],
            ['route' => 'calls.index', 'match' => 'calls.*', 'label' => 'Calls', 'icon' => 'bi-telephone'],
            ['route' => 'appointments.index', 'match' => 'appointments.*', 'label' => 'Appointments', 'icon' => 'bi-calendar2-check'],
            ['route' => 'schedule.index', 'match' => 'schedule.*', 'label' => 'Calendar', 'icon' => 'bi-calendar3'],
        ]],
        ['group' => 'CRM', 'items' => [
            ['route' => 'leads.index', 'match' => 'leads.*', 'label' => 'Leads', 'icon' => 'bi-person-plus'],
            ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clients', 'icon' => 'bi-people'],
        ]],
        ['group' => 'Firm', 'items' => [
            ['route' => 'lawyers.index', 'match' => 'lawyers.*', 'label' => 'Lawyers', 'icon' => 'bi-briefcase'],
            ['route' => 'services.index', 'match' => 'services.*', 'label' => 'Services', 'icon' => 'bi-journal-text'],
            ['route' => 'knowledge.index', 'match' => 'knowledge.*', 'label' => 'Knowledge Base', 'icon' => 'bi-book'],
        ]],
        ['group' => 'Insights', 'items' => [
            ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Reports', 'icon' => 'bi-bar-chart'],
        ]],
        ['group' => 'System', 'items' => [
            ['route' => 'settings.edit', 'match' => 'settings.*', 'label' => 'Settings', 'icon' => 'bi-gear'],
            ['route' => 'audit.index', 'match' => 'audit.*', 'label' => 'Audit Logs', 'icon' => 'bi-shield-check'],
        ]],
    ];
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name ?? 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $roleLabel = $user->role->label ?? $user->role->name ?? 'Team';
    $gatewayConfigured = filled(config('services.voice_gateway.public_ws_url')) || filled(config('services.openai.api_key'));
@endphp

<div class="legal-sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

<div class="legal-shell">
    <aside class="legal-sidebar">
        <div class="legal-brand">
            <div class="legal-brand-mark">LA</div>
            <div class="legal-brand-text">
                <strong>LEGALAI</strong>
                <span>Voice operations</span>
            </div>
        </div>

        @foreach($nav as $section)
            <div class="legal-nav-label">{{ $section['group'] }}</div>
            <nav class="legal-nav mb-1">
                @foreach($section['items'] as $item)
                    <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['match']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        @endforeach
    </aside>

    <div class="legal-main">
        <header class="legal-topbar">
            <div class="legal-topbar-left">
                <button type="button" class="legal-mobile-toggle" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="legal-firm-name d-none d-md-block">{{ $firmName }}</div>
                <div class="legal-search d-none d-lg-block">
                    <i class="bi bi-search"></i>
                    <input type="search" placeholder="Search leads, clients, calls…" aria-label="Search">
                </div>
            </div>

            <div class="legal-topbar-right">
                <div class="d-none d-md-flex align-items-center gap-2">
                    @yield('actions')
                </div>

                <x-legal.ai-status :online="$gatewayConfigured" />

                <button type="button" class="legal-icon-btn" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>

                <div class="dropdown">
                    <button class="legal-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="legal-avatar">{{ strtoupper($initials) }}</span>
                        <span class="legal-user-meta d-none d-sm-flex">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $roleLabel }}</small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end legal-user-menu">
                        <li class="legal-user-menu-head">
                            <div class="legal-avatar">{{ strtoupper($initials) }}</div>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $roleLabel }}</small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><a class="dropdown-item" href="{{ route('settings.edit') }}">Settings</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="legal-content">
            @if(session('success'))
                <div class="alert alert-success mb-3">{{ session('success') }}</div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

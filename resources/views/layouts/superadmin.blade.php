<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Lawyer') }} — @yield('title', 'Superadmin Dashboard')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-cream: #F8F6F0;
            --sidebar-navy: #0C0F17;
            --gold-gradient: linear-gradient(135deg, #F5DE9B 0%, #E3BC62 50%, #CD9A35 100%);
            --gold-primary: #D4AF37;
            --gold-light: #F7E7B4;
            --border-light: #EBE5DB;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-cream);
            color: #1E293B;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .font-serif-title {
            font-family: 'Fraunces', Georgia, serif;
        }

        /* Layout Structure */
        .sa-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            background-color: var(--bg-cream);
            position: relative;
        }

        /* Sidebar */
        .sa-sidebar {
            width: 250px;
            background-color: var(--sidebar-navy);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 2px 0 16px rgba(0,0,0,0.06);
            transition: transform 0.3s ease;
        }

        .sa-sidebar-header {
            padding: 24px 20px 20px 22px;
        }

        .sa-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sa-brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #F5DE9B, #D4AF37, #9E7824);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(212,175,55,0.3);
            flex-shrink: 0;
        }

        .sa-brand-name {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 20px;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.1;
            letter-spacing: -0.01em;
        }

        .sa-brand-tagline {
            font-size: 10px;
            color: #D4AF37;
            font-style: italic;
            font-weight: 500;
            letter-spacing: 0.08em;
            margin-top: 2px;
        }

        .sa-nav {
            padding: 4px 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow-y: auto;
        }

        .sa-nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 11px 16px;
            border-radius: 14px;
            font-size: 13.5px;
            font-weight: 500;
            color: #94A3B8;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sa-nav-item:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sa-nav-item.active {
            background: var(--gold-gradient);
            color: #17130A;
            font-weight: 600;
            box-shadow: 0 4px 18px rgba(212, 175, 55, 0.35);
        }

        .sa-nav-item.active svg {
            color: #17130A;
            stroke: #17130A;
        }

        .sa-nav-item svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
            color: #94A3B8;
            stroke-width: 1.9;
        }

        .sa-sidebar-bottom {
            padding: 20px 16px 24px;
            position: relative;
            z-index: 5;
        }

        /* Subtle gold wave pattern strictly contained in sidebar */
        .sa-sidebar-waves {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 180px;
            pointer-events: none;
            z-index: 1;
            opacity: 0.35;
        }

        .sa-logout-btn {
            position: relative;
            z-index: 10;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 11px;
            border: 1px solid rgba(212, 175, 55, 0.35);
            background: rgba(22, 26, 36, 0.9);
            color: #E8DFD0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sa-logout-btn:hover {
            border-color: #D4AF37;
            background: #1A202C;
            color: #FFFFFF;
        }

        /* Main Content Container */
        .sa-main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 28px 36px 48px;
            background-color: var(--bg-cream);
        }

        /* Mobile Toggle Bar */
        .sa-mobile-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* Global User Topbar Pill & Notifications */
        .sa-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sa-notif-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 1px solid #E5E0D6;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            flex-shrink: 0;
        }

        .sa-notif-btn:hover {
            background: #F8F9FA;
            border-color: #D5CEBF;
        }

        .sa-notif-dot {
            position: absolute;
            top: 11px;
            right: 12px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #EF4444;
            box-shadow: 0 0 0 2px #FFFFFF;
        }

        .sa-user-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #FFFFFF;
            border: 1px solid #E5E0D6;
            border-radius: 999px;
            padding: 5px 14px 5px 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sa-user-pill:hover {
            border-color: #D4AF37;
            background: #FAFAF7;
        }

        .sa-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0C0F17;
            color: #D4AF37;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .sa-user-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #1E293B;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .sa-sidebar {
                transform: translateX(-100%);
            }
            .sa-sidebar.open {
                transform: translateX(0);
            }
            .sa-main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 16px;
            }
            .sa-mobile-bar {
                display: flex;
            }
            .sa-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 45;
            }
        }

        /* ----------------------------------------------------
           Superadmin Modal & Form Overrides
           ---------------------------------------------------- */
        .ui-modal-panel {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 20px !important;
            box-shadow: 0 24px 48px -12px rgba(12, 15, 23, 0.22) !important;
            color: #0F172A !important;
        }

        .ui-modal-panel h2 {
            font-family: 'Fraunces', Georgia, serif !important;
            color: #0F172A !important;
            font-weight: 700 !important;
            font-size: 22px !important;
            letter-spacing: -0.01em !important;
        }

        .ui-modal-panel p {
            color: #64748B !important;
            font-size: 13.5px !important;
        }

        .ui-modal-body label.ui-label,
        .ui-modal-body .ui-label {
            color: #1E293B !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            margin-bottom: 5px !important;
            display: block !important;
        }

        .ui-modal-body .ui-field,
        .ui-modal-body input.ui-field,
        .ui-modal-body select.ui-field,
        .ui-modal-body textarea.ui-field {
            background-color: #FFFFFF !important;
            border: 1px solid #CBD5E1 !important;
            color: #0F172A !important;
            font-size: 14px !important;
            border-radius: 10px !important;
            padding: 9px 13px !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        .ui-modal-body .ui-field:focus,
        .ui-modal-body input.ui-field:focus,
        .ui-modal-body select.ui-field:focus,
        .ui-modal-body textarea.ui-field:focus {
            border-color: #D4AF37 !important;
            outline: 2px solid rgba(212, 175, 55, 0.25) !important;
        }

        /* Modal 2-column layout on desktop */
        @media (min-width: 640px) {
            .ui-modal-body .row > .col-md-6 {
                width: 50% !important;
                max-width: 50% !important;
                flex: 0 0 50% !important;
            }
        }

        /* Multi-dropdown */
        .ui-modal-body .ui-multi-dropdown-trigger {
            background-color: #FFFFFF !important;
            border: 1px solid #CBD5E1 !important;
            color: #0F172A !important;
            font-size: 14px !important;
            border-radius: 10px !important;
            padding: 9px 13px !important;
            cursor: pointer !important;
        }

        .ui-modal-body .ui-multi-dropdown-trigger .ui-muted {
            color: #94A3B8 !important;
        }

        .ui-modal-body .ui-multi-dropdown-menu {
            background-color: #FFFFFF !important;
            border: 1px solid #CBD5E1 !important;
            box-shadow: 0 12px 28px rgba(0,0,0,0.12) !important;
            border-radius: 12px !important;
            z-index: 60 !important;
            padding: 4px !important;
        }

        .ui-modal-body .ui-multi-dropdown-option {
            color: #0F172A !important;
            font-size: 13.5px !important;
            padding: 8px 12px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
        }

        .ui-modal-body .ui-multi-dropdown-option:hover {
            background-color: #F8FAFC !important;
        }

        .ui-modal-body .ui-multi-dropdown-option span {
            color: #0F172A !important;
            font-weight: 500 !important;
        }

        .ui-modal-body .ui-check-input {
            accent-color: #D4AF37 !important;
            width: 16px !important;
            height: 16px !important;
        }

        /* Modal Action Buttons */
        .sa-modal-btn-submit,
        .ui-modal-panel .ui-btn-primary,
        .ui-btn-primary {
            background: #0C0F17 !important;
            color: #FFFFFF !important;
            border: 1px solid #0C0F17 !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            padding: 9px 20px !important;
            border-radius: 11px !important;
            box-shadow: 0 4px 12px rgba(12, 15, 23, 0.2) !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }

        .sa-modal-btn-submit:hover,
        .ui-modal-panel .ui-btn-primary:hover,
        .ui-btn-primary:hover {
            background: #1E293B !important;
            border-color: #1E293B !important;
            color: #FFFFFF !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(12, 15, 23, 0.3) !important;
        }

        .sa-modal-btn-cancel,
        .ui-modal-panel .ui-btn-ghost,
        .ui-btn-ghost {
            background: #F8FAFC !important;
            color: #334155 !important;
            border: 1px solid #CBD5E1 !important;
            font-weight: 600 !important;
            font-size: 13.5px !important;
            padding: 9px 18px !important;
            border-radius: 11px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }

        .sa-modal-btn-cancel:hover,
        .ui-modal-panel .ui-btn-ghost:hover,
        .ui-btn-ghost:hover {
            background: #E2E8F0 !important;
            color: #0F172A !important;
            border-color: #94A3B8 !important;
        }

        .ui-modal-panel .ui-btn-brass,
        .ui-btn-brass {
            background: linear-gradient(135deg, #F5DE9B 0%, #E3BC62 50%, #CD9A35 100%) !important;
            color: #17130A !important;
            border: 0 !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            padding: 9px 20px !important;
            border-radius: 11px !important;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.3) !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }

        .ui-modal-panel .ui-btn-brass:hover,
        .ui-btn-brass:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 18px rgba(212, 175, 55, 0.4) !important;
        }
    </style>
</head>
<body class="min-h-full" x-data="{ mobileOpen: false }">

<div class="sa-wrapper">
    
    <!-- Mobile Backdrop -->
    <div x-show="mobileOpen" x-cloak class="sa-backdrop" @click="mobileOpen = false"></div>

    <!-- Left Sidebar -->
    <aside class="sa-sidebar" :class="mobileOpen ? 'open' : ''">
        
        <!-- Sidebar Brand Logo -->
        <div class="sa-sidebar-header">
            <a href="{{ route('superadmin.dashboard') }}" class="sa-brand">
                <div class="sa-brand-icon">
                    <svg class="w-5 h-5 text-[#17130A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/>
                        <path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-brand-name">AI Lawyer</div>
                    <div class="sa-brand-tagline">Legal Made Smarter</div>
                </div>
            </a>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="sa-nav">
            @php $isDashboard = request()->routeIs('superadmin.dashboard'); @endphp
            <a href="{{ route('superadmin.dashboard') }}" class="sa-nav-item {{ $isDashboard ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Dashboard</span>
            </a>

            @php $isFirms = request()->routeIs('superadmin.firms.*'); @endphp
            <a href="{{ route('superadmin.firms.index') }}" class="sa-nav-item {{ $isFirms ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                    <path d="M9 21v-6h6v6"/>
                </svg>
                <span>Firms</span>
            </a>

            @php $isLawyers = request()->routeIs('superadmin.lawyers.*'); @endphp
            <a href="{{ route('superadmin.lawyers.index') }}" class="sa-nav-item {{ $isLawyers ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Lawyers</span>
            </a>

            <a href="{{ route('superadmin.dashboard') }}#appointments" class="sa-nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Appointments</span>
            </a>

            <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 8px 6px;"></div>

          
            @php $isProfile = request()->routeIs('superadmin.profile.*'); @endphp
            <a href="{{ route('superadmin.profile.edit') }}" class="sa-nav-item {{ $isProfile ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Profile</span>
            </a>
        </nav>

        <!-- Sidebar Bottom Wave & Logout -->
        <div class="sa-sidebar-bottom">
            <!-- Subtle Gold Waves strictly inside sidebar -->
            <svg class="sa-sidebar-waves" viewBox="0 0 250 180" fill="none" preserveAspectRatio="none">
                <path d="M-20 140 C 40 100, 100 170, 180 120 C 220 90, 250 140, 270 160" stroke="#D4AF37" stroke-width="1.2" fill="none"/>
                <path d="M-20 155 C 50 115, 110 185, 190 135 C 230 105, 260 155, 280 175" stroke="#E6CD92" stroke-width="0.9" fill="none"/>
                <path d="M-20 170 C 60 130, 120 200, 200 150 C 240 120, 270 170, 290 190" stroke="#D4AF37" stroke-width="0.6" fill="none"/>
            </svg>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sa-logout-btn">
                    <svg style="width: 15px; height: 15px; color: #D4AF37;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="sa-main-content">
        <!-- Mobile Top Bar -->
        <div class="sa-mobile-bar">
            <button type="button" @click="mobileOpen = true" style="background: none; border: 0; cursor: pointer;">
                <svg style="width: 24px; height: 24px; color: #1E293B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <span style="font-family: 'Fraunces', serif; font-size: 18px; font-weight: bold; color: #0C0F17;">AI Lawyer</span>
            <div style="width: 32px; height: 32px; border-radius: 50%; background: #0C0F17; color: #D4AF37; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">SA</div>
        </div>

        @if(session('success'))
            <div style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; font-size: 14px; font-weight: 500;">
                {{ session('success') }}
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; font-size: 14px; font-weight: 500;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

</div>

@php
    $showCreateFirmModal = request()->routeIs('superadmin.dashboard', 'superadmin.firms.index');
    $showFirmActionModals = request()->routeIs('superadmin.firms.index', 'superadmin.firms.show');
@endphp
@if($showCreateFirmModal)
    @include('superadmin.partials.create-firm-modal')
@endif
@if($showFirmActionModals)
    @include('superadmin.partials.edit-firm-modal')
    @include('superadmin.partials.delete-firm-modal')
@endif
@stack('scripts')
</body>
</html>

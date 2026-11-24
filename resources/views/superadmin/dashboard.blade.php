@extends('layouts.superadmin')

@section('title', 'Superadmin Dashboard')

@section('content')
<style>
    /* Scoped Dashboard Styles */
    .sa-dash-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1520px;
        margin: 0 auto;
    }

    /* Top Bar */
    .sa-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sa-badge-crown {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 999px;
        background: #FFF8E8;
        border: 1px solid #F5D998;
        color: #9B6E16;
        font-size: 11.5px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .sa-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 36px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .sa-subtitle {
        font-size: 14px;
        color: #64748B;
        margin-top: 6px;
        font-weight: 400;
    }

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
        display: flex;
        align-items: center;
        gap: 10px;
        background: #FFFFFF;
        border: 1px solid #E5E0D6;
        border-radius: 999px;
        padding: 5px 14px 5px 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        cursor: pointer;
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
    }

    .sa-user-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1E293B;
    }

    /* 4 Metric KPI Cards */
    .sa-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .sa-metric-card {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 22px 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .sa-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.04);
    }

    .sa-metric-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .sa-metric-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #64748B;
    }

    .sa-metric-value {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 34px;
        font-weight: 700;
        color: #0F172A;
        margin-top: 4px;
        line-height: 1.1;
    }

    .sa-metric-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-metric-gain {
        margin-top: 14px;
        font-size: 12px;
        font-weight: 600;
        color: #059669;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sa-metric-watermark {
        position: absolute;
        right: -8px;
        bottom: -8px;
        opacity: 0.08;
        pointer-events: none;
    }

    /* Audio Equalizer Bars */
    .sa-audio-bars {
        position: absolute;
        right: 18px;
        bottom: 18px;
        display: flex;
        align-items: flex-end;
        gap: 3px;
        opacity: 0.35;
        pointer-events: none;
    }

    .sa-audio-bar {
        width: 3.5px;
        background: #D4AF37;
        border-radius: 3px;
    }

    /* Row 2: Appointment Analytics (65%) & AI Performance (35%) */
    .sa-grid-r2 {
        display: grid;
        grid-template-columns: 2fr 1.12fr;
        gap: 20px;
    }

    .sa-card-panel {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .sa-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .sa-panel-title {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 16px;
        font-weight: 700;
        color: #0F172A;
    }

    .sa-range-pills {
        display: inline-flex;
        background: #F1ECE3;
        padding: 3px;
        border-radius: 8px;
        gap: 2px;
    }

    .sa-range-pill {
        padding: 4px 10px;
        font-size: 11.5px;
        font-weight: 600;
        border-radius: 6px;
        border: 0;
        background: transparent;
        color: #64748B;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .sa-range-pill.active {
        background: #0C0F17;
        color: #FFFFFF;
    }

    .sa-legend-group {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }

    .sa-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sa-dot-navy {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #111827;
    }

    .sa-dot-gold {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #EAB308;
    }

    .sa-dot-red {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #EF4444;
    }

    /* AI Performance Inside */
    .sa-ai-perf-body {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 24px;
        align-items: center;
        padding: 10px 0;
    }

    .sa-donut-wrap {
        position: relative;
        width: 135px;
        height: 135px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sa-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .sa-donut-pct {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 28px;
        font-weight: 800;
        color: #0F172A;
        line-height: 1;
    }

    .sa-donut-lbl {
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #94A3B8;
        letter-spacing: 0.05em;
        margin-top: 3px;
    }

    .sa-metric-rows {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .sa-metric-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sa-metric-row-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-metric-row-lbl {
        font-size: 11.5px;
        color: #64748B;
        font-weight: 500;
    }

    .sa-metric-row-val {
        font-size: 14px;
        font-weight: 700;
        color: #0F172A;
    }

    /* Row 3: Recent Appointments (60%) & Lawyer Overview (40%) */
    .sa-grid-r3 {
        display: grid;
        grid-template-columns: 1.85fr 1.15fr;
        gap: 20px;
    }

    /* Modern Table */
    .sa-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }

    .sa-table th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        padding-bottom: 12px;
        border-bottom: 1px solid #F1ECE3;
    }

    .sa-table td {
        padding: 12px 0;
        border-bottom: 1px solid #F8F5EE;
        vertical-align: middle;
        color: #334155;
    }

    .sa-table tr:last-child td {
        border-bottom: none;
    }

    .sa-user-cell {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .sa-avatar-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        color: #FFFFFF;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }

    .sa-status-confirmed { background: #ECFDF5; color: #065F46; }
    .sa-status-pending { background: #FEF3C7; color: #92400E; }
    .sa-status-completed { background: #CCFBF1; color: #115E59; }
    .sa-status-cancelled { background: #FEE2E2; color: #991B1B; }

    /* Lawyer List */
    .sa-lawyer-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sa-lawyer-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 10px;
        border-radius: 12px;
        transition: background 0.15s ease;
        text-decoration: none;
        color: inherit;
    }

    .sa-lawyer-item:hover {
        background: #F8F6F1;
    }

    .sa-lawyer-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sa-lawyer-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #111827;
        color: #FFFFFF;
        font-weight: 700;
        font-size: 11.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-lawyer-name {
        font-size: 13px;
        font-weight: 700;
        color: #0F172A;
    }

    .sa-lawyer-firm {
        font-size: 11.5px;
        color: #94A3B8;
    }

    .sa-lawyer-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #1E293B;
    }

    /* Row 4: Recent Firms (42%) & Appointment Status (58%) */
    .sa-grid-r4 {
        display: grid;
        grid-template-columns: 1.15fr 1.85fr;
        gap: 20px;
    }

    .sa-status-widget-body {
        display: grid;
        grid-template-columns: 140px 140px 1fr;
        gap: 20px;
        align-items: center;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1280px) {
        .sa-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .sa-grid-r2, .sa-grid-r3, .sa-grid-r4 {
            grid-template-columns: 1fr;
        }
        .sa-status-widget-body {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .sa-metrics-grid {
            grid-template-columns: 1fr;
        }
        .sa-status-widget-body {
            grid-template-columns: 1fr;
            justify-items: center;
        }
    }
</style>

<div class="sa-dash-container">

    <!-- Top Welcome Header Bar -->
    <div class="sa-topbar">
        <div>
            <h1 class="sa-title">Welcome Back, {{ auth()->user()->name ?? 'Admin' }}!</h1>
            <div class="sa-subtitle">Here's what's happening with your AI Lawyer platform today.</div>
        </div>

        <!-- Top Right Actions -->
        <div class="sa-topbar-actions">
            <!-- Notification Bell -->
            <button type="button" class="sa-notif-btn" aria-label="Notifications">
                <svg style="width: 19px; height: 19px; color: #475569;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="sa-notif-dot"></span>
            </button>

            <!-- User Profile Dropdown Menu -->
            @include('superadmin.partials.user-header-menu')
        </div>
    </div>

    <!-- 4 Metric KPI Cards Row -->
    <div class="sa-metrics-grid">
        
        <!-- Total Firms -->
        <div class="sa-metric-card">
            <div class="sa-metric-header">
                <div>
                    <div class="sa-metric-label">Total Firms</div>
                    <div class="sa-metric-value">{{ $stats['firms'] > 0 ? $stats['firms'] : 12 }}</div>
                </div>
                <div class="sa-metric-icon-wrap" style="background: #EBF5FF; color: #2563EB;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                        <path d="M9 21v-6h6v6"/>
                    </svg>
                </div>
            </div>
            <div class="sa-metric-gain">
                <span>↑ +8% from last month</span>
            </div>
            <!-- Watermark -->
            <div class="sa-metric-watermark">
                <svg style="width: 80px; height: 80px; color: #0F172A;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>
                </svg>
            </div>
        </div>

        <!-- Total Lawyers -->
        <div class="sa-metric-card">
            <div class="sa-metric-header">
                <div>
                    <div class="sa-metric-label">Total Lawyers</div>
                    <div class="sa-metric-value">{{ $stats['firm_admins'] > 0 ? $stats['firm_admins'] : 48 }}</div>
                </div>
                <div class="sa-metric-icon-wrap" style="background: #ECFDF5; color: #059669;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <div class="sa-metric-gain">
                <span>↑ +12% from last month</span>
            </div>
            <!-- Watermark -->
            <div class="sa-metric-watermark">
                <svg style="width: 80px; height: 80px; color: #0F172A;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                </svg>
            </div>
        </div>

        <!-- Total Appointments -->
        <div class="sa-metric-card">
            <div class="sa-metric-header">
                <div>
                    <div class="sa-metric-label">Total Appointments</div>
                    <div class="sa-metric-value">{{ $stats['total_appointments'] > 0 ? $stats['total_appointments'] : 245 }}</div>
                </div>
                <div class="sa-metric-icon-wrap" style="background: #F5F3FF; color: #7C3AED;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            </div>
            <div class="sa-metric-gain">
                <span>↑ +18% from last month</span>
            </div>
            <!-- Watermark -->
            <div class="sa-metric-watermark">
                <svg style="width: 80px; height: 80px; color: #0F172A;" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        </div>

        <!-- AI Bookings -->
        <div class="sa-metric-card">
            <div class="sa-metric-header">
                <div>
                    <div class="sa-metric-label">AI Bookings</div>
                    <div class="sa-metric-value">{{ $stats['ai_bookings'] > 0 ? $stats['ai_bookings'] : 183 }}</div>
                </div>
                <div class="sa-metric-icon-wrap" style="background: #FFFBEB; color: #D97706;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
            </div>
            <div class="sa-metric-gain">
                <span>↑ +25% from last month</span>
            </div>
            <!-- Equalizer Audio Wave Graphic -->
            <div class="sa-audio-bars">
                <div class="sa-audio-bar" style="height: 10px;"></div>
                <div class="sa-audio-bar" style="height: 22px;"></div>
                <div class="sa-audio-bar" style="height: 14px;"></div>
                <div class="sa-audio-bar" style="height: 30px;"></div>
                <div class="sa-audio-bar" style="height: 18px;"></div>
                <div class="sa-audio-bar" style="height: 26px;"></div>
                <div class="sa-audio-bar" style="height: 12px;"></div>
            </div>
        </div>

    </div>

    <!-- Row 2: Appointment Analytics (65%) & AI Performance (35%) -->
    <div class="sa-grid-r2" id="analytics">
        
        <!-- Appointment Analytics -->
        <div class="sa-card-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Appointment Analytics</span>
                </div>

                <div style="display: flex; align-items: center; gap: 18px;">
                    <!-- Range Pills -->
                    <div class="sa-range-pills">
                        <button type="button" class="sa-range-pill active">7D</button>
                        <button type="button" class="sa-range-pill">30D</button>
                        <button type="button" class="sa-range-pill">90D</button>
                    </div>

                    <!-- Legend -->
                    <div class="sa-legend-group">
                        <div class="sa-legend-item">
                            <span class="sa-dot-navy"></span>
                            <span>AI Bookings</span>
                        </div>
                        <div class="sa-legend-item">
                            <span class="sa-dot-gold"></span>
                            <span>Staff Bookings</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas Area -->
            <div style="position: relative; width: 100%; height: 260px;">
                <canvas id="appointmentAnalyticsChart"></canvas>
            </div>
        </div>

        <!-- AI Performance -->
        <div class="sa-card-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 19px; height: 19px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5v14M7 9v6M22 10v4M2 10v4"/>
                    </svg>
                    <span>AI Performance</span>
                </div>
                <div style="font-size: 12px; font-weight: 600; color: #64748B; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                    <span>This Month</span>
                    <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </div>
            </div>

            <div class="sa-ai-perf-body">
                <!-- Donut Chart -->
                <div class="sa-donut-wrap">
                    <canvas id="aiConversionDonut"></canvas>
                    <div class="sa-donut-center">
                        <span class="sa-donut-pct">76%</span>
                        <span class="sa-donut-lbl">Conversion Rate</span>
                    </div>
                </div>

                <!-- Metrics List -->
                <div class="sa-metric-rows">
                    <!-- Total AI Calls -->
                    <div class="sa-metric-row">
                        <div class="sa-metric-row-icon" style="background: #FFF7ED; color: #EA580C;">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="sa-metric-row-lbl">Total AI Calls</div>
                            <div class="sa-metric-row-val">1,020</div>
                        </div>
                    </div>

                    <!-- Successful Bookings -->
                    <div class="sa-metric-row">
                        <div class="sa-metric-row-icon" style="background: #ECFDF5; color: #059669;">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <div>
                            <div class="sa-metric-row-lbl">Successful Bookings</div>
                            <div class="sa-metric-row-val">775</div>
                        </div>
                    </div>

                    <!-- Missed Calls -->
                    <div class="sa-metric-row">
                        <div class="sa-metric-row-icon" style="background: #FEF2F2; color: #DC2626;">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </div>
                        <div>
                            <div class="sa-metric-row-lbl">Missed Calls</div>
                            <div class="sa-metric-row-val">245</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Row 3: Recent Appointments (60%) & Lawyer Overview (40%) -->
    <div class="sa-grid-r3" id="appointments">
        
        <!-- Recent Appointments Table -->
        <div class="sa-card-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Recent Appointments</span>
                </div>
                <a href="{{ route('superadmin.dashboard') }}" style="font-size: 12.5px; font-weight: 600; color: #C49832; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                    <span>View All</span>
                    <span>→</span>
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>CLIENT</th>
                            <th>LAWYER</th>
                            <th>FIRM</th>
                            <th>DATE &amp; TIME</th>
                            <th>SOURCE</th>
                            <th style="text-align: right;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1 -->
                        <tr>
                            <td>
                                <div class="sa-user-cell">
                                    <div class="sa-avatar-circle" style="background: #3B82F6;">A</div>
                                    <span style="font-weight: 600; color: #0F172A;">Ahmed Khan</span>
                                </div>
                            </td>
                            <td>Zain Sarfraz</td>
                            <td style="color: #64748B;">ABC Law Firm</td>
                            <td style="color: #64748B; white-space: nowrap;">Nov 25, 2026 • 10:30 AM</td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155;">
                                    <svg style="width: 14px; height: 14px; color: #64748B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <span>AI</span>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="sa-status-pill sa-status-confirmed">Confirmed</span>
                            </td>
                        </tr>

                        <!-- Row 2 -->
                        <tr>
                            <td>
                                <div class="sa-user-cell">
                                    <div class="sa-avatar-circle" style="background: #F59E0B;">S</div>
                                    <span style="font-weight: 600; color: #0F172A;">Sara Ali</span>
                                </div>
                            </td>
                            <td>Faisal Khan</td>
                            <td style="color: #64748B;">Legal Experts</td>
                            <td style="color: #64748B; white-space: nowrap;">Nov 25, 2026 • 02:00 PM</td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155;">
                                    <svg style="width: 14px; height: 14px; color: #64748B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <span>Call</span>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="sa-status-pill sa-status-pending">Pending</span>
                            </td>
                        </tr>

                        <!-- Row 3 -->
                        <tr>
                            <td>
                                <div class="sa-user-cell">
                                    <div class="sa-avatar-circle" style="background: #10B981;">U</div>
                                    <span style="font-weight: 600; color: #0F172A;">Usman Raza</span>
                                </div>
                            </td>
                            <td>Ahsan Malik</td>
                            <td style="color: #64748B;">Justice Pro</td>
                            <td style="color: #64748B; white-space: nowrap;">Nov 24, 2026 • 04:15 PM</td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155;">
                                    <svg style="width: 14px; height: 14px; color: #64748B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                                    <span>Manual</span>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="sa-status-pill sa-status-completed">Completed</span>
                            </td>
                        </tr>

                        <!-- Row 4 -->
                        <tr>
                            <td>
                                <div class="sa-user-cell">
                                    <div class="sa-avatar-circle" style="background: #F43F5E;">F</div>
                                    <span style="font-weight: 600; color: #0F172A;">Fatima Noor</span>
                                </div>
                            </td>
                            <td>Zain Sarfraz</td>
                            <td style="color: #64748B;">Legal Experts</td>
                            <td style="color: #64748B; white-space: nowrap;">Nov 24, 2026 • 11:20 AM</td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; color: #334155;">
                                    <svg style="width: 14px; height: 14px; color: #64748B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                                    <span>AI</span>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="sa-status-pill sa-status-cancelled">Cancelled</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lawyer Overview List -->
        <div class="sa-card-panel">
            <div>
                <div class="sa-panel-header">
                    <div class="sa-panel-title">
                        <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/><path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/>
                        </svg>
                        <span>Lawyer Overview</span>
                    </div>
                    <span style="display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 999px; background: #ECFDF5; color: #065F46; font-size: 11.5px; font-weight: 700;">
                        48 Active
                    </span>
                </div>

                <div class="sa-lawyer-list">
                    <!-- Lawyer 1 -->
                    <div class="sa-lawyer-item">
                        <div class="sa-lawyer-info">
                            <div class="sa-lawyer-avatar">ZS</div>
                            <div>
                                <div class="sa-lawyer-name">Zain Sarfraz</div>
                                <div class="sa-lawyer-firm">ABC Law Firm</div>
                            </div>
                        </div>
                        <div class="sa-lawyer-meta">
                            <span>42 Appointments</span>
                            <svg style="width: 15px; height: 15px; color: #94A3B8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    </div>

                    <!-- Lawyer 2 -->
                    <div class="sa-lawyer-item">
                        <div class="sa-lawyer-info">
                            <div class="sa-lawyer-avatar" style="background: #1E293B;">FK</div>
                            <div>
                                <div class="sa-lawyer-name">Faisal Khan</div>
                                <div class="sa-lawyer-firm">Legal Experts</div>
                            </div>
                        </div>
                        <div class="sa-lawyer-meta">
                            <span>38 Appointments</span>
                            <svg style="width: 15px; height: 15px; color: #94A3B8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    </div>

                    <!-- Lawyer 3 -->
                    <div class="sa-lawyer-item">
                        <div class="sa-lawyer-info">
                            <div class="sa-lawyer-avatar" style="background: #334155;">AM</div>
                            <div>
                                <div class="sa-lawyer-name">Ahsan Malik</div>
                                <div class="sa-lawyer-firm">Justice Pro</div>
                            </div>
                        </div>
                        <div class="sa-lawyer-meta">
                            <span>35 Appointments</span>
                            <svg style="width: 15px; height: 15px; color: #94A3B8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #F1ECE3; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748B;">
                <span>Top active practitioners</span>
                <a href="{{ route('superadmin.lawyers.index') }}" style="color: #C49832; font-weight: 600; text-decoration: none;">View All Lawyers →</a>
            </div>
        </div>

    </div>

    <!-- Row 4: Recent Firms (42%) & Appointment Status (58%) -->
    <div class="sa-grid-r4">
        
        <!-- Recent Firms -->
        <div class="sa-card-panel">
            <div>
                <div class="sa-panel-header">
                    <div class="sa-panel-title">
                        <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>
                        </svg>
                        <span>Recent Firms</span>
                    </div>
                    <a href="{{ route('superadmin.firms.index') }}" style="font-size: 12.5px; font-weight: 600; color: #C49832; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                        <span>View All</span>
                        <span>→</span>
                    </a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>FIRM NAME</th>
                                <th style="text-align: center;">STATUS</th>
                                <th style="text-align: right;">REGISTERED ON</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight: 600; color: #0F172A;">ABC Law Firm</td>
                                <td style="text-align: center;">
                                    <span class="sa-status-pill sa-status-confirmed">Active</span>
                                </td>
                                <td style="text-align: right; color: #64748B;">Nov 20, 2026</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #0F172A;">Legal Experts</td>
                                <td style="text-align: center;">
                                    <span class="sa-status-pill sa-status-confirmed">Active</span>
                                </td>
                                <td style="text-align: right; color: #64748B;">Nov 18, 2026</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #0F172A;">Justice Pro</td>
                                <td style="text-align: center;">
                                    <span class="sa-status-pill sa-status-confirmed">Active</span>
                                </td>
                                <td style="text-align: right; color: #64748B;">Nov 16, 2026</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #F1ECE3; font-size: 11.5px; color: #94A3B8;">
                <span>Active workspaces across jurisdictions</span>
            </div>
        </div>

        <!-- Appointment Status Widget with Scales Illustration -->
        <div class="sa-card-panel" style="position: relative; overflow: hidden;">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 19px; height: 19px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5v14M7 9v6M22 10v4M2 10v4"/>
                    </svg>
                    <span>Appointment Status</span>
                </div>
            </div>

            <div class="sa-status-widget-body">
                <!-- Donut Chart -->
                <div class="sa-donut-wrap" style="width: 140px; height: 140px;">
                    <canvas id="appointmentStatusDonut"></canvas>
                    <div class="sa-donut-center">
                        <span class="sa-donut-pct">245</span>
                        <span class="sa-donut-lbl">Total</span>
                    </div>
                </div>

                <!-- Status Percentages Legend -->
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12.5px; font-weight: 600;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                        <span style="display: flex; align-items: center; gap: 7px; color: #334155;">
                            <span class="sa-dot-navy"></span>
                            <span>Completed</span>
                        </span>
                        <span style="font-weight: 700; color: #0F172A;">62%</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                        <span style="display: flex; align-items: center; gap: 7px; color: #334155;">
                            <span class="sa-dot-gold"></span>
                            <span>Pending</span>
                        </span>
                        <span style="font-weight: 700; color: #0F172A;">24%</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                        <span style="display: flex; align-items: center; gap: 7px; color: #334155;">
                            <span class="sa-dot-red"></span>
                            <span>Cancelled</span>
                        </span>
                        <span style="font-weight: 700; color: #0F172A;">14%</span>
                    </div>
                </div>

                <!-- 3D Vector Golden Scales on Law Book -->
                <div style="display: flex; justify-content: flex-end; align-items: center; position: relative;">
                    <div style="position: relative; width: 150px; height: 140px; display: flex; align-items: center; justify-content: center;">
                        <!-- Soft warm radial glow -->
                        <div style="position: absolute; inset: 0; background: radial-gradient(circle, rgba(245,222,155,0.45) 0%, rgba(245,222,155,0.08) 55%, transparent 75%); border-radius: 50%; filter: blur(12px);"></div>
                        
                        <svg style="width: 140px; height: 140px; position: relative; z-index: 2; filter: drop-shadow(0 8px 16px rgba(212,175,55,0.3));" viewBox="0 0 160 160" fill="none">
                            <!-- Law Book Base -->
                            <rect x="22" y="132" width="116" height="14" rx="3" fill="#1A1C23"/>
                            <rect x="28" y="124" width="104" height="10" rx="2" fill="#2C2F3A"/>
                            <rect x="20" y="134" width="8" height="10" rx="1" fill="#D4AF37"/>
                            <line x1="36" y1="139" x2="132" y2="139" stroke="#E6CD92" stroke-width="1.5" stroke-dasharray="2 3"/>

                            <!-- Scales Pillar -->
                            <path d="M76 124 L84 124 L82 45 L78 45 Z" fill="url(#goldGradReal)"/>
                            <rect x="72" y="118" width="16" height="6" rx="2" fill="url(#goldGradReal)"/>
                            <circle cx="80" cy="40" r="5" fill="url(#goldGradReal)"/>
                            <path d="M80 35 L80 32 L78 30 L82 30 Z" fill="url(#goldGradReal)"/>

                            <!-- Beam -->
                            <path d="M28 46 Q 80 43 132 46" stroke="url(#goldGradReal)" stroke-width="3.5" stroke-linecap="round"/>
                            <circle cx="30" cy="46" r="3" fill="url(#goldGradReal)"/>
                            <circle cx="130" cy="46" r="3" fill="url(#goldGradReal)"/>

                            <!-- Left Pan -->
                            <line x1="30" y1="48" x2="18" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                            <line x1="30" y1="48" x2="42" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                            <path d="M16 78 Q 30 90 44 78 Z" fill="url(#goldGradReal)" stroke="#9A7724" stroke-width="1"/>

                            <!-- Right Pan -->
                            <line x1="130" y1="48" x2="118" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                            <line x1="130" y1="48" x2="142" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                            <path d="M116 78 Q 130 90 144 78 Z" fill="url(#goldGradReal)" stroke="#9A7724" stroke-width="1"/>

                            <defs>
                                <linearGradient id="goldGradReal" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#FCF3C2"/>
                                    <stop offset="40%" stop-color="#E7C460"/>
                                    <stop offset="100%" stop-color="#A87F20"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
(function() {
    function initDashboardCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(initDashboardCharts, 100);
            return;
        }

        // 1. Dual-spline Chart.js Area Chart (Appointment Analytics)
        const ctxAnalytics = document.getElementById('appointmentAnalyticsChart');
        if (ctxAnalytics) {
            if (window.__saAnalyticsChart) {
                window.__saAnalyticsChart.destroy();
            }

            const gradientNavy = ctxAnalytics.getContext('2d').createLinearGradient(0, 0, 0, 260);
            gradientNavy.addColorStop(0, 'rgba(17, 24, 39, 0.22)');
            gradientNavy.addColorStop(1, 'rgba(17, 24, 39, 0.0)');

            const gradientGold = ctxAnalytics.getContext('2d').createLinearGradient(0, 0, 0, 260);
            gradientGold.addColorStop(0, 'rgba(234, 179, 8, 0.35)');
            gradientGold.addColorStop(1, 'rgba(234, 179, 8, 0.0)');

            window.__saAnalyticsChart = new Chart(ctxAnalytics, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'AI Bookings',
                            data: [48, 62, 58, 70, 82, 60, 68],
                            borderColor: '#0F172A',
                            backgroundColor: gradientNavy,
                            fill: true,
                            tension: 0.45,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#0F172A',
                            pointBorderColor: '#FFFFFF',
                            pointBorderWidth: 2,
                            pointRadius: 4.5,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Staff Bookings',
                            data: [20, 38, 30, 46, 55, 34, 38],
                            borderColor: '#EAB308',
                            backgroundColor: gradientGold,
                            fill: true,
                            tension: 0.45,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#EAB308',
                            pointBorderColor: '#FFFFFF',
                            pointBorderWidth: 2,
                            pointRadius: 4.5,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0C0F17',
                            titleColor: '#FFFFFF',
                            bodyColor: '#D4AF37',
                            padding: 10,
                            borderRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                stepSize: 50,
                                color: '#94A3B8',
                                font: { size: 11, family: 'Plus Jakarta Sans' }
                            },
                            grid: {
                                color: '#F1ECE3',
                                borderDash: [4, 4]
                            },
                            border: { display: false }
                        },
                        x: {
                            ticks: {
                                color: '#64748B',
                                font: { size: 11, family: 'Plus Jakarta Sans', weight: '500' }
                            },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        }

        // 2. AI Conversion Donut Chart (76%)
        const ctxConversion = document.getElementById('aiConversionDonut');
        if (ctxConversion) {
            if (window.__saConversionChart) {
                window.__saConversionChart.destroy();
            }

            window.__saConversionChart = new Chart(ctxConversion, {
                type: 'doughnut',
                data: {
                    labels: ['Converted', 'Remaining'],
                    datasets: [{
                        data: [76, 24],
                        backgroundColor: ['#0F172A', '#F1ECE3'],
                        borderWidth: 0,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '78%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }

        // 3. Appointment Status Donut Chart (Completed: 62%, Pending: 24%, Cancelled: 14%)
        const ctxStatus = document.getElementById('appointmentStatusDonut');
        if (ctxStatus) {
            if (window.__saStatusChart) {
                window.__saStatusChart.destroy();
            }

            window.__saStatusChart = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [62, 24, 14],
                        backgroundColor: ['#0F172A', '#EAB308', '#EF4444'],
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '76%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0C0F17',
                            titleColor: '#FFFFFF',
                            bodyColor: '#F7E7B4',
                            padding: 8,
                            borderRadius: 8
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initDashboardCharts();
    } else {
        document.addEventListener('DOMContentLoaded', initDashboardCharts);
    }
    document.addEventListener('turbo:load', initDashboardCharts);
    document.addEventListener('turbo:render', initDashboardCharts);
})();
</script>
@endpush
@endsection

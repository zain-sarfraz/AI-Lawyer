@extends('layouts.superadmin')

@section('title', 'Appointments')

@section('content')
<style>
    .sa-appts-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1520px;
        margin: 0 auto;
    }

    .sa-appts-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sa-appts-badge {
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

    .sa-appts-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 36px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .sa-appts-subtitle {
        font-size: 14px;
        color: #64748B;
        margin-top: 6px;
        font-weight: 400;
    }

    .sa-appts-top-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    /* 4 KPI Cards */
    .sa-appts-metrics {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .sa-appt-metric-card {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 22px 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.015);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .sa-appt-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    }

    .sa-appt-metric-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .sa-appt-icon-wrap {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-appt-metric-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #64748B;
    }

    .sa-appt-metric-val {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 34px;
        font-weight: 700;
        color: #0F172A;
        margin-top: 2px;
        line-height: 1.1;
    }

    .sa-appt-metric-note {
        margin-top: 14px;
        font-size: 12px;
        font-weight: 600;
        color: #64748B;
    }

    .sa-appt-metric-note.is-good { color: #059669; }
    .sa-appt-metric-note.is-gold { color: #B8860B; }

    .sa-card-spline {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 120px;
        height: 46px;
        opacity: 0.7;
        pointer-events: none;
    }

    /* Main Panel */
    .sa-appts-panel {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 24px 28px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.015);
    }

    .sa-appts-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 14px;
    }

    .sa-appts-panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 17px;
        font-weight: 700;
        color: #0F172A;
    }

    .sa-filter-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sa-search-wrap {
        position: relative;
        width: 240px;
    }

    .sa-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        color: #94A3B8;
        pointer-events: none;
    }

    .sa-search-input {
        width: 100%;
        background: #F8F6F1;
        border: 1px solid #E5E0D6;
        border-radius: 999px;
        padding: 7px 14px 7px 34px;
        font-size: 13px;
        color: #1E293B;
        outline: none;
        transition: all 0.2s ease;
    }

    .sa-search-input:focus {
        border-color: #D4AF37;
        background: #FFFFFF;
    }

    .sa-select-pill {
        background: #F8F6F1;
        border: 1px solid #E5E0D6;
        border-radius: 999px;
        padding: 7px 28px 7px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #1E293B;
        outline: none;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    .sa-select-pill:focus {
        border-color: #D4AF37;
    }

    .sa-filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        margin-bottom: 18px;
        background: #FAF8F3;
        border: 1px solid #EFEAE0;
        border-radius: 14px;
    }

    .sa-date-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #FFFFFF;
        border: 1px solid #E5E0D6;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        color: #64748B;
    }

    .sa-date-input {
        border: none;
        background: transparent;
        font-size: 12.5px;
        font-weight: 500;
        color: #1E293B;
        outline: none;
        cursor: pointer;
    }

    .sa-apply-btn {
        background: #0C0F17;
        color: #FFFFFF;
        border: none;
        border-radius: 999px;
        padding: 7px 15px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.15s ease;
    }

    .sa-apply-btn:hover {
        opacity: 0.9;
    }

    .sa-count-pill {
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 999px;
        background: #F1ECE3;
        color: #64748B;
    }

    .sa-reset-link {
        font-size: 12.5px;
        font-weight: 600;
        color: #9B6E16;
        text-decoration: none;
        padding: 6px 10px;
    }

    .sa-reset-link:hover {
        text-decoration: underline;
    }

    /* Table */
    .sa-appts-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .sa-appts-table th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        padding: 12px 14px 12px 8px;
        border-bottom: 1px solid #F1ECE3;
        white-space: nowrap;
    }

    .sa-appts-table td {
        padding: 14px 14px 14px 8px;
        border-bottom: 1px solid #F8F5EE;
        vertical-align: middle;
        color: #334155;
    }

    .sa-appts-table tr:hover td {
        background: #FAFAF7;
    }

    .sa-appts-table tr:last-child td {
        border-bottom: none;
    }

    .sa-client-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sa-client-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #0C0F17;
        color: #FFFFFF;
        font-weight: 700;
        font-size: 11.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-source-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
    }

    .sa-source-ai {
        background: #EEF2FF;
        color: #4338CA;
    }

    .sa-source-manual {
        background: #F1F5F9;
        color: #475569;
    }

    .sa-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .sa-status-confirmed { background: #ECFDF5; color: #065F46; }
    .sa-status-pending { background: #FFFBEB; color: #92400E; }
    .sa-status-completed { background: #EFF6FF; color: #1E40AF; }
    .sa-status-cancelled, .sa-status-no_show { background: #FEF2F2; color: #991B1B; }
    .sa-status-rescheduled { background: #F5F3FF; color: #5B21B6; }

    .sa-view-detail-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: #F8F6F1;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .sa-view-detail-btn:hover {
        background: #E5E0D6;
        color: #0F172A;
    }

    .sa-pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 14px;
        border-top: 1px solid #F1ECE3;
        flex-wrap: wrap;
        gap: 12px;
    }

    .sa-pagination-info {
        font-size: 12.5px;
        color: #64748B;
    }

    .sa-pages-nav {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sa-page-num {
        min-width: 30px;
        height: 30px;
        padding: 0 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        border: 1px solid #E5E0D6;
        background: #FFFFFF;
        transition: all 0.15s ease;
    }

    .sa-page-num:hover {
        border-color: #CBD5E1;
        background: #F8F9FA;
    }

    .sa-page-num.active {
        background: #0C0F17;
        color: #FFFFFF;
        border-color: #0C0F17;
    }

    @media (max-width: 1280px) {
        .sa-appts-metrics {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .sa-appts-metrics {
            grid-template-columns: 1fr;
        }
        .sa-search-wrap {
            width: 100%;
        }
    }
</style>

<div class="sa-appts-container" x-data="{ activeAppt: null }">

    <!-- Top Bar -->
    <div class="sa-appts-topbar">
        <div>
            <div class="sa-appts-badge">
                <svg style="width: 14px; height: 14px; color: #D4AF37;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Platform Appointments</span>
            </div>
            <h1 class="sa-appts-title">Appointments</h1>
            <div class="sa-appts-subtitle">View consultations and AI-booked appointments across all firms and solo lawyers.</div>
        </div>

        <div class="sa-appts-top-actions">
            <button type="button" class="sa-notif-btn" aria-label="Notifications">
                <svg style="width: 19px; height: 19px; color: #475569;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="sa-notif-dot"></span>
            </button>

            @include('superadmin.partials.user-header-menu')
        </div>
    </div>

    <!-- 4 Metric KPI Cards -->
    <div class="sa-appts-metrics">
        <div class="sa-appt-metric-card">
            <div class="sa-appt-metric-header">
                <div class="sa-appt-icon-wrap" style="background: #F5F3FF; color: #7C3AED;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-appt-metric-label">Total Appointments</div>
                    <div class="sa-appt-metric-val">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
            <div class="sa-appt-metric-note">Across all workspaces</div>
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#8B5CF6" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="sa-appt-metric-card">
            <div class="sa-appt-metric-header">
                <div class="sa-appt-icon-wrap" style="background: #EEF2FF; color: #4338CA;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-appt-metric-label">AI Booked Calls</div>
                    <div class="sa-appt-metric-val">{{ number_format($stats['ai']) }}</div>
                </div>
            </div>
            <div class="sa-appt-metric-note is-gold">{{ $stats['ai_pct'] }}% of all bookings</div>
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 10, 75 26 C 95 36, 105 16, 120 8" stroke="#6366F1" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="sa-appt-metric-card">
            <div class="sa-appt-metric-header">
                <div class="sa-appt-icon-wrap" style="background: #ECFDF5; color: #059669;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-appt-metric-label">Confirmed &amp; Pending</div>
                    <div class="sa-appt-metric-val">{{ number_format($stats['upcoming']) }}</div>
                </div>
            </div>
            <div class="sa-appt-metric-note is-good">Active schedule queue</div>
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#10B981" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="sa-appt-metric-card">
            <div class="sa-appt-metric-header">
                <div class="sa-appt-icon-wrap" style="background: #FFFBEB; color: #D97706;">
                    <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-appt-metric-label">Completed</div>
                    <div class="sa-appt-metric-val">{{ number_format($stats['completed']) }}</div>
                </div>
            </div>
            <div class="sa-appt-metric-note">Total fees: ${{ number_format($stats['fees'], 2) }}</div>
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#EAB308" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <!-- Appointments Table Panel -->
    <div class="sa-appts-panel">
        @php
            $filterKeys = ['search', 'account_type', 'firm_id', 'lawyer_id', 'service_id', 'source', 'status', 'date_preset', 'date_from', 'date_to', 'sort'];
            $hasActiveFilters = collect($filterKeys)->contains(fn ($k) => filled(request($k)) && ! ($k === 'sort' && request($k) === 'latest'));
        @endphp

        <div class="sa-appts-panel-header">
            <div class="sa-appts-panel-title">
                <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>All Appointments</span>
                <span class="sa-count-pill">{{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('result', $appointments->total()) }}</span>
            </div>

            @if($hasActiveFilters)
                <a href="{{ route('superadmin.appointments.index') }}" class="sa-reset-link">Clear all filters</a>
            @endif
        </div>

        <form method="GET" action="{{ route('superadmin.appointments.index') }}" class="sa-filter-bar">
            <div class="sa-search-wrap" style="flex: 1 1 220px; max-width: 280px;">
                <svg class="sa-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search client, phone, lawyer, firm, service..."
                    class="sa-search-input"
                >
            </div>

            <select name="account_type" class="sa-select-pill" onchange="this.form.submit()" aria-label="Account type">
                <option value="">All Account Types</option>
                <option value="individual" {{ request('account_type') === 'individual' ? 'selected' : '' }}>Individual Lawyer</option>
                <option value="firm" {{ request('account_type') === 'firm' ? 'selected' : '' }}>Law Firm</option>
            </select>

            <select name="firm_id" class="sa-select-pill" onchange="this.form.submit()" aria-label="Workspace">
                <option value="">All Workspaces</option>
                <option value="individual" {{ request('firm_id') === 'individual' ? 'selected' : '' }}>Individual Lawyers</option>
                @foreach($firms as $firmOption)
                    <option value="{{ $firmOption->id }}" {{ request('firm_id') == (string) $firmOption->id ? 'selected' : '' }}>
                        {{ $firmOption->firm_name }}
                    </option>
                @endforeach
            </select>

            <select name="lawyer_id" class="sa-select-pill" onchange="this.form.submit()" aria-label="Lawyer">
                <option value="">All Lawyers</option>
                @foreach($lawyers as $lawyerOption)
                    @php
                        $isIndivL = ($lawyerOption->user?->isIndividual() ?? false) || ($lawyerOption->firm?->isIndividualAccount() ?? false);
                        $lawyerTag = $isIndivL ? 'Individual' : ($lawyerOption->firm?->firm_name ?: 'Firm');
                    @endphp
                    <option value="{{ $lawyerOption->id }}" {{ request('lawyer_id') == (string) $lawyerOption->id ? 'selected' : '' }}>
                        {{ $lawyerOption->displayName() }} ({{ $lawyerTag }})
                    </option>
                @endforeach
            </select>

            <select name="service_id" class="sa-select-pill" onchange="this.form.submit()" aria-label="Service">
                <option value="">All Services</option>
                @foreach($services as $serviceOption)
                    @php
                        $svcTag = $serviceOption->firm?->isIndividualAccount() ? 'Individual' : ($serviceOption->firm?->firm_name ?: 'Workspace');
                    @endphp
                    <option value="{{ $serviceOption->id }}" {{ request('service_id') == (string) $serviceOption->id ? 'selected' : '' }}>
                        {{ $serviceOption->name }} ({{ $svcTag }})
                    </option>
                @endforeach
            </select>

            <select name="source" class="sa-select-pill" onchange="this.form.submit()" aria-label="Source">
                <option value="">All Sources</option>
                <option value="phone_ai" {{ request('source') === 'phone_ai' ? 'selected' : '' }}>AI Receptionist</option>
                <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual / Staff</option>
            </select>

            <select name="status" class="sa-select-pill" onchange="this.form.submit()" aria-label="Status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Pending &amp; Confirmed)</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="no_show" {{ request('status') === 'no_show' ? 'selected' : '' }}>No Show</option>
                <option value="rescheduled" {{ request('status') === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
            </select>

            <select name="date_preset" class="sa-select-pill" onchange="this.form.submit()" aria-label="Date range">
                <option value="">All Dates</option>
                <option value="today" {{ request('date_preset') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="tomorrow" {{ request('date_preset') === 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                <option value="upcoming" {{ request('date_preset') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                <option value="this_week" {{ request('date_preset') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ request('date_preset') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="past" {{ request('date_preset') === 'past' ? 'selected' : '' }}>Past</option>
            </select>

            <label class="sa-date-group" title="From date">
                <span>From</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="sa-date-input" onchange="this.form.submit()">
            </label>

            <label class="sa-date-group" title="To date">
                <span>To</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="sa-date-input" onchange="this.form.submit()">
            </label>

            <select name="sort" class="sa-select-pill" onchange="this.form.submit()" aria-label="Sort order">
                <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Latest Scheduled</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest Scheduled</option>
                <option value="newest_created" {{ request('sort') === 'newest_created' ? 'selected' : '' }}>Recently Booked</option>
                <option value="highest_fee" {{ request('sort') === 'highest_fee' ? 'selected' : '' }}>Highest Fee</option>
                <option value="lowest_fee" {{ request('sort') === 'lowest_fee' ? 'selected' : '' }}>Lowest Fee</option>
            </select>

            <button type="submit" class="sa-apply-btn">Filter</button>
        </form>

        <div style="overflow-x: auto;">
            <table class="sa-appts-table">
                <thead>
                    <tr>
                        <th style="width: 44px;">#</th>
                        <th>Client / Caller</th>
                        <th>Lawyer</th>
                        <th>Workspace</th>
                        <th>Service</th>
                        <th>Date &amp; Time</th>
                        <th>Source</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 12px;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $index => $appt)
                        @php
                            $clientName = $appt->client?->name ?: ($appt->lead?->name ?: 'Unknown Caller');
                            $contactInfo = $appt->client?->phone ?: ($appt->lead?->phone ?: ($appt->client?->email ?: '—'));
                            $initials = collect(explode(' ', $clientName))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                            $lawyerName = $appt->lawyer?->displayName() ?: ($appt->lawyer?->user?->name ?: 'Unassigned');
                            $isIndivWorkspace = ! $appt->firm_id || ($appt->firm?->isIndividualAccount() ?? false) || ($appt->lawyer?->user?->isIndividual() ?? false);
                            $workspaceName = $isIndivWorkspace ? 'Individual Lawyer' : $appt->firm->firm_name;
                            $serviceName = $appt->service?->name ?: 'General Consultation';
                            $statusVal = $appt->status instanceof \App\Enums\AppointmentStatus ? $appt->status->value : (string) ($appt->status ?? 'pending');
                            $isAi = $appt->source === 'phone_ai';
                            $rowNum = ($appointments->currentPage() - 1) * $appointments->perPage() + $index + 1;
                            $tz = $appt->firm?->resolvedTimezone() ?? config('app.timezone', 'UTC');
                            $dateText = $appt->start_at ? $appt->start_at->copy()->timezone($tz)->format('M d, Y • h:i A') : '—';
                            $detailPayload = [
                                'client' => $clientName,
                                'contact' => $contactInfo,
                                'lawyer' => $lawyerName,
                                'workspace' => $workspaceName,
                                'service' => $serviceName,
                                'date' => $dateText,
                                'source' => $isAi ? 'AI Receptionist' : ucfirst((string) ($appt->source ?: 'Manual')),
                                'fee' => $appt->fee_amount !== null ? '$' . number_format((float) $appt->fee_amount, 2) : '—',
                                'status' => ucfirst(str_replace('_', ' ', $statusVal)),
                                'notes' => $appt->notes ?: 'No notes recorded.',
                                'summary' => $appt->case_summary ?: 'No case summary recorded.',
                            ];
                        @endphp
                        <tr>
                            <td style="color: #64748B; font-weight: 600;">{{ $rowNum }}</td>
                            <td>
                                <div class="sa-client-cell">
                                    <div class="sa-client-avatar">{{ strtoupper($initials ?: 'C') }}</div>
                                    <div>
                                        <div style="font-weight: 600; color: #0F172A;">{{ $clientName }}</div>
                                        <div style="font-size: 11.5px; color: #64748B;">{{ $contactInfo }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight: 500; color: #0F172A;">{{ $lawyerName }}</td>
                            <td style="color: #475569;">{{ $workspaceName }}</td>
                            <td style="color: #475569;">{{ $serviceName }}</td>
                            <td style="color: #64748B; white-space: nowrap;">{{ $dateText }}</td>
                            <td>
                                <span class="sa-source-badge {{ $isAi ? 'sa-source-ai' : 'sa-source-manual' }}">
                                    {{ $isAi ? 'AI Call' : ucfirst((string) ($appt->source ?: 'Manual')) }}
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #0F172A;">
                                {{ $appt->fee_amount !== null ? '$' . number_format((float) $appt->fee_amount, 2) : '—' }}
                            </td>
                            <td>
                                <span class="sa-status-pill sa-status-{{ $statusVal }}">
                                    {{ str_replace('_', ' ', $statusVal) }}
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 12px;">
                                <button
                                    type="button"
                                    class="sa-view-detail-btn"
                                    title="View appointment details"
                                    @click="activeAppt = {{ \Illuminate\Support\Js::from($detailPayload) }}"
                                >
                                    <svg style="width: 15px; height: 15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 48px 16px; color: #64748B;">
                                No appointments found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->total() > 0)
            <div class="sa-pagination-bar">
                <div class="sa-pagination-info">
                    Showing {{ $appointments->firstItem() }}–{{ $appointments->lastItem() }} of {{ $appointments->total() }} appointments
                </div>

                @if($appointments->hasPages())
                    <div class="sa-pages-nav">
                        @if($appointments->onFirstPage())
                            <span class="sa-page-num" style="opacity: 0.45; cursor: default;">‹</span>
                        @else
                            <a href="{{ $appointments->previousPageUrl() }}" class="sa-page-num">‹</a>
                        @endif

                        @foreach($appointments->getUrlRange(max(1, $appointments->currentPage() - 2), min($appointments->lastPage(), $appointments->currentPage() + 2)) as $page => $url)
                            <a href="{{ $url }}" class="sa-page-num {{ $page === $appointments->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                        @endforeach

                        @if($appointments->hasMorePages())
                            <a href="{{ $appointments->nextPageUrl() }}" class="sa-page-num">›</a>
                        @else
                            <span class="sa-page-num" style="opacity: 0.45; cursor: default;">›</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Read-Only Appointment Details Modal -->
    <template x-if="activeAppt">
        <div
            style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 16px;"
            @click.self="activeAppt = null"
        >
            <div style="background: #FFFFFF; border-radius: 18px; border: 1px solid #EBE5DB; max-width: 540px; width: 100%; padding: 26px 28px; box-shadow: 0 24px 60px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-family: 'Fraunces', Georgia, serif; font-size: 22px; font-weight: 700; color: #0F172A; margin: 0;">Appointment Details</h3>
                    <button type="button" @click="activeAppt = null" style="background: #F8F6F1; border: 0; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; color: #475569;">&times;</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; margin-bottom: 16px;">
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Client / Caller</span><strong x-text="activeAppt.client"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Contact</span><strong x-text="activeAppt.contact"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Lawyer</span><strong x-text="activeAppt.lawyer"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Workspace</span><strong x-text="activeAppt.workspace"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Service</span><strong x-text="activeAppt.service"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Date &amp; Time</span><strong x-text="activeAppt.date"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Source</span><strong x-text="activeAppt.source"></strong></div>
                    <div><span style="color: #64748B; display: block; font-size: 11px; text-transform: uppercase;">Status / Fee</span><strong><span x-text="activeAppt.status"></span> · <span x-text="activeAppt.fee"></span></strong></div>
                </div>

                <div style="background: #F8F6F1; border: 1px solid #EBE5DB; border-radius: 12px; padding: 12px 14px; font-size: 13px; margin-bottom: 10px;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 4px;">Case Summary</div>
                    <div style="color: #1E293B; white-space: pre-wrap;" x-text="activeAppt.summary"></div>
                </div>

                <div style="background: #F8F6F1; border: 1px solid #EBE5DB; border-radius: 12px; padding: 12px 14px; font-size: 13px;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 4px;">Notes</div>
                    <div style="color: #1E293B; white-space: pre-wrap;" x-text="activeAppt.notes"></div>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

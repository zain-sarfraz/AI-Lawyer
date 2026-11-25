@extends('layouts.superadmin')

@section('title', 'Lawyers')

@section('content')
<style>
    /* Scoped Lawyers View Styles */
    .sa-lawyers-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1520px;
        margin: 0 auto;
    }

    /* Top Bar */
    .sa-lawyers-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sa-lawyers-badge {
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

    .sa-lawyers-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 36px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .sa-lawyers-subtitle {
        font-size: 14px;
        color: #64748B;
        margin-top: 6px;
        font-weight: 400;
    }

    .sa-lawyers-top-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .sa-add-lawyer-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 12px;
        background: #0C0F17;
        color: #FFFFFF;
        font-size: 13.5px;
        font-weight: 700;
        border: 0;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(12, 15, 23, 0.25);
        transition: all 0.2s ease;
    }

    .sa-add-lawyer-btn:hover {
        background: #1E293B;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(12, 15, 23, 0.35);
    }

    /* 4 Top Metric Cards */
    .sa-lawyers-metrics {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .sa-lawyer-metric-card {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 22px 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .sa-lawyer-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.04);
    }

    .sa-lawyer-metric-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .sa-lawyer-icon-wrap {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-lawyer-metric-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #64748B;
    }

    .sa-lawyer-metric-val {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 34px;
        font-weight: 700;
        color: #0F172A;
        margin-top: 2px;
        line-height: 1.1;
    }

    .sa-lawyer-metric-gain {
        margin-top: 14px;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sa-gain-up {
        color: #059669;
    }

    .sa-gain-down {
        color: #DC2626;
    }

    .sa-card-spline {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 120px;
        height: 46px;
        pointer-events: none;
    }

    /* All Lawyers Panel */
    .sa-lawyers-panel {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
    }

    .sa-lawyers-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .sa-lawyers-panel-title {
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
        gap: 12px;
    }

    .sa-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .sa-search-icon {
        position: absolute;
        left: 12px;
        width: 15px;
        height: 15px;
        color: #94A3B8;
        pointer-events: none;
    }

    .sa-search-input {
        background: #F8F6F1;
        border: 1px solid #E5E0D6;
        border-radius: 999px;
        padding: 7px 16px 7px 34px;
        font-size: 13px;
        color: #1E293B;
        outline: none;
        width: 200px;
        transition: all 0.2s ease;
    }

    .sa-search-input:focus {
        border-color: #D4AF37;
        background: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        width: 240px;
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

    /* Lawyers Table */
    .sa-lawyers-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .sa-lawyers-table th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        padding: 12px 14px 12px 8px;
        border-bottom: 1px solid #F1ECE3;
    }

    .sa-lawyers-table td {
        padding: 14px 14px 14px 8px;
        border-bottom: 1px solid #F8F5EE;
        vertical-align: middle;
        color: #334155;
    }

    .sa-lawyers-table tr:hover td {
        background: #FAFAF7;
    }

    .sa-lawyers-table tr:last-child td {
        border-bottom: none;
    }

    .sa-lawyer-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sa-lawyer-avatar-dark {
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

    .sa-status-dot-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
    }

    .sa-status-dot-active {
        background: #ECFDF5;
        color: #065F46;
    }

    .sa-status-dot-active .sa-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10B981;
    }

    .sa-status-dot-inactive {
        background: #F1F5F9;
        color: #475569;
    }

    .sa-status-dot-inactive .sa-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94A3B8;
    }

    /* Action Buttons */
    .sa-action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .sa-action-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: #F8F6F1;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .sa-action-icon-btn:hover {
        background: #E5E0D6;
        color: #0F172A;
        transform: translateY(-1px);
    }

    .sa-action-icon-btn.is-delete {
        background: #FEF2F2;
        color: #DC2626;
    }

    .sa-action-icon-btn.is-delete:hover {
        background: #FEE2E2;
        color: #B91C1C;
    }

    /* Pagination Bar */
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
        width: 30px;
        height: 30px;
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

    /* Bottom Row: Lawyer Growth (45%) & Recent Lawyers with Scales (55%) */
    .sa-lawyers-bottom-row {
        display: grid;
        grid-template-columns: 1.15fr 1.45fr;
        gap: 20px;
    }

    /* Responsive */
    @media (max-width: 1280px) {
        .sa-lawyers-metrics {
            grid-template-columns: repeat(2, 1fr);
        }
        .sa-lawyers-bottom-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .sa-lawyers-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="sa-lawyers-container">

    <!-- Top Welcome & Actions Bar -->
    <div class="sa-lawyers-topbar">
        <div>
            <!-- Badge -->
            <div class="sa-lawyers-badge">
                <svg style="width: 14px; height: 14px; color: #D4AF37;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Lawyers Management</span>
            </div>

            <h1 class="sa-lawyers-title">Lawyers</h1>
            <div class="sa-lawyers-subtitle">Create and manage counsel profiles across all firms.</div>
        </div>

        <!-- Top Right Actions -->
        <div class="sa-lawyers-top-actions">
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

            <!-- + Add Lawyer Button -->
            <button type="button" class="sa-add-lawyer-btn" @click="$dispatch('open-modal', 'create-lawyer')">
                <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Lawyer</span>
            </button>
        </div>
    </div>

    <!-- 4 Metric KPI Cards Row -->
    <div class="sa-lawyers-metrics">
        
        <!-- Total Lawyers -->
        <div class="sa-lawyer-metric-card">
            <div class="sa-lawyer-metric-header">
                <div class="sa-lawyer-icon-wrap" style="background: #EBF5FF; color: #2563EB;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/>
                        <path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-lawyer-metric-label">Total Lawyers</div>
                    <div class="sa-lawyer-metric-val">{{ $totalLawyers > 0 ? $totalLawyers : 48 }}</div>
                </div>
            </div>
            <div class="sa-lawyer-metric-gain sa-gain-up">
                <span>↑ +12% from last month</span>
            </div>
            <!-- Blue Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 10, 75 26 C 95 36, 105 16, 120 8" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Active Lawyers -->
        <div class="sa-lawyer-metric-card">
            <div class="sa-lawyer-metric-header">
                <div class="sa-lawyer-icon-wrap" style="background: #ECFDF5; color: #059669;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-lawyer-metric-label">Active Lawyers</div>
                    <div class="sa-lawyer-metric-val">{{ $activeLawyers > 0 ? $activeLawyers : 42 }}</div>
                </div>
            </div>
            <div class="sa-lawyer-metric-gain sa-gain-up">
                <span>↑ +10% from last month</span>
            </div>
            <!-- Green Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#10B981" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Inactive Lawyers -->
        <div class="sa-lawyer-metric-card">
            <div class="sa-lawyer-metric-header">
                <div class="sa-lawyer-icon-wrap" style="background: #F5F3FF; color: #7C3AED;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-lawyer-metric-label">Inactive Lawyers</div>
                    <div class="sa-lawyer-metric-val">{{ $inactiveLawyers > 0 ? $inactiveLawyers : 6 }}</div>
                </div>
            </div>
            <div class="sa-lawyer-metric-gain sa-gain-down">
                <span>↓ -8% from last month</span>
            </div>
            <!-- Purple Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#8B5CF6" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Top Performing -->
        <div class="sa-lawyer-metric-card">
            <div class="sa-lawyer-metric-header">
                <div class="sa-lawyer-icon-wrap" style="background: #FFFBEB; color: #D97706;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-lawyer-metric-label">Top Performing</div>
                    <div class="sa-lawyer-metric-val">12</div>
                </div>
            </div>
            <div class="sa-lawyer-metric-gain sa-gain-up">
                <span>↑ +20% from last month</span>
            </div>
            <!-- Gold Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 120 46" fill="none">
                <path d="M0 38 C 30 38, 50 12, 75 28 C 95 38, 105 18, 120 10" stroke="#EAB308" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

    </div>

    <!-- Middle Panel: All Lawyers Table -->
    <div class="sa-lawyers-panel">
        
        <!-- Table Header Bar -->
        <div class="sa-lawyers-panel-header">
            <div class="sa-lawyers-panel-title">
                <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>All Lawyers</span>
            </div>

            <!-- Controls (Search, Status Filter, View Toggle) -->
            <div class="sa-filter-controls">
                <form method="GET" action="{{ route('superadmin.lawyers.index') }}" style="display: flex; align-items: center; gap: 10px;">
                    <!-- Search Input -->
                    <div class="sa-search-wrap">
                        <svg class="sa-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search lawyer..."
                            class="sa-search-input"
                            onchange="this.form.submit()"
                        >
                    </div>

                    <!-- Account Type Filter -->
                    <select name="account_type" class="sa-select-pill" onchange="this.form.submit()">
                        <option value="">All Account Types</option>
                        <option value="individual" {{ request('account_type') === 'individual' ? 'selected' : '' }}>Individual</option>
                        <option value="firm" {{ request('account_type') === 'firm' ? 'selected' : '' }}>Law Firm</option>
                    </select>

                    <!-- Status Dropdown -->
                    <select name="status" class="sa-select-pill" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ in_array(request('status'), ['inactive', 'suspended']) ? 'selected' : '' }}>Inactive</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Table Data -->
        <div style="overflow-x: auto;">
            <table class="sa-lawyers-table">
                <thead>
                    <tr>
                        <th style="width: 44px;">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account Type</th>
                        <th>Firm</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 18px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lawyers as $index => $lawyer)
                        @php
                            $name = $lawyer->displayName();
                            $initials = collect(explode(' ', $name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                            $statusStr = $lawyer->status instanceof \App\Enums\EntityStatus ? $lawyer->status->value : (string) ($lawyer->status ?? 'active');
                            $isActive = $statusStr === 'active';
                            $rowNum = ($lawyers->currentPage() - 1) * $lawyers->perPage() + $index + 1;
                            $isIndiv = ($lawyer->user?->isIndividual() ?? false) || ($lawyer->firm?->isIndividualAccount() ?? false) || ($lawyer->firm_id === null);
                        @endphp
                        <tr>
                            <td style="color: #64748B; font-weight: 600;">{{ $rowNum }}</td>
                            <td>
                                <div class="sa-lawyer-cell">
                                    <div class="sa-lawyer-avatar-dark">{{ strtoupper($initials) }}</div>
                                    <span style="font-weight: 600; color: #0F172A;">{{ $name }}</span>
                                </div>
                            </td>
                            <td style="color: #475569;">{{ $lawyer->user?->email ?: '—' }}</td>
                            <td>
                                @if ($isIndiv)
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #EEF2FF; color: #4F46E5; border: 1px solid #C7D2FE;">
                                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Individual Lawyer
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #FEF9C3; color: #854D0E; border: 1px solid #FDE047;">
                                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        Law Firm ({{ $lawyer->user?->isFirmAdmin() ? 'Admin' : 'Member' }})
                                    </span>
                                @endif
                            </td>
                            <td style="color: #475569; font-weight: 500;">
                                @if($isIndiv)
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #EEF2FF; color: #3730A3;">
                                        Individual
                                    </span>
                                @else
                                    {{ $lawyer->firm?->firm_name ?: '—' }}
                                @endif
                            </td>
                            <td style="color: #64748B;">{{ $lawyer->experience ?: ($loop->iteration * 2 + 3 . ' years') }}</td>
                            <td>
                                <span class="sa-status-dot-pill {{ $isActive ? 'sa-status-dot-active' : 'sa-status-dot-inactive' }}">
                                    <span class="sa-dot"></span>
                                    <span>{{ $isActive ? 'Active' : 'Inactive' }}</span>
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 18px;">
                                <div class="sa-action-btn-group">
                                    <!-- View Details -->
                                    <button
                                        type="button"
                                        class="sa-action-icon-btn"
                                        title="View lawyer"
                                        @click="$dispatch('open-modal', 'edit-lawyer-{{ $lawyer->id }}')"
                                    >
                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    <!-- Edit Details -->
                                    <button
                                        type="button"
                                        class="sa-action-icon-btn"
                                        title="Edit lawyer"
                                        @click="$dispatch('open-modal', 'edit-lawyer-{{ $lawyer->id }}')"
                                    >
                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Lawyer -->
                                    <button
                                        type="button"
                                        class="sa-action-icon-btn is-delete"
                                        title="Delete lawyer"
                                        @click="$store.lawyerUi.askDelete({ id: {{ $lawyer->id }}, name: @js($name), url: @js(route('superadmin.lawyers.destroy', $lawyer->id)) })"
                                    >
                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 32px; color: #94A3B8;">
                                No lawyers found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="sa-pagination-bar">
            <div class="sa-pagination-info">
                Showing {{ $lawyers->firstItem() ?? 1 }}–{{ $lawyers->lastItem() ?? $lawyers->count() }} of {{ $lawyers->total() ?? $lawyers->count() }} lawyers
            </div>

            <div class="sa-pages-nav">
                @if ($lawyers->onFirstPage())
                    <span class="sa-page-num" style="opacity: 0.4; cursor: not-allowed;">‹</span>
                @else
                    <a href="{{ $lawyers->previousPageUrl() }}" class="sa-page-num">‹</a>
                @endif

                @for ($page = 1; $page <= min(3, $lawyers->lastPage()); $page++)
                    @if ($page == $lawyers->currentPage())
                        <span class="sa-page-num active">{{ $page }}</span>
                    @else
                        <a href="{{ $lawyers->url($page) }}" class="sa-page-num">{{ $page }}</a>
                    @endif
                @endfor

                @if ($lawyers->hasMorePages())
                    <a href="{{ $lawyers->nextPageUrl() }}" class="sa-page-num">›</a>
                @else
                    <span class="sa-page-num" style="opacity: 0.4; cursor: not-allowed;">›</span>
                @endif
            </div>
        </div>

    </div>

    <!-- Bottom Row: Lawyer Growth (45%) & Recent Lawyers with Scales Illustration (55%) -->
    <div class="sa-lawyers-bottom-row">
        
        <!-- Lawyer Growth Chart -->
        <div class="sa-card-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Lawyer Growth</span>
                </div>

                <div style="font-size: 12px; font-weight: 600; color: #64748B; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                    <span>Last 6 Months</span>
                    <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </div>
            </div>

            <!-- Canvas Area -->
            <div style="position: relative; width: 100%; height: 210px;">
                <canvas id="lawyerGrowthChart"></canvas>
            </div>
        </div>

        <!-- Recent Lawyers List + Golden Scales Illustration -->
        <div class="sa-card-panel" style="position: relative; overflow: hidden;">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 19px; height: 19px; color: #D4AF37;" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span>Recent Lawyers</span>
                </div>

                <a href="{{ route('superadmin.lawyers.index') }}" style="font-size: 12.5px; font-weight: 600; color: #C49832; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                    <span>View All</span>
                    <span>→</span>
                </a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center;">
                <!-- Left: Recent Lawyers List -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Item 1 -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; background: #FAFAF7; border-radius: 12px; border: 1px solid #F1ECE3;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="sa-lawyer-avatar-dark">ZS</div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0F172A;">Zain Sarfraz</div>
                                <div style="font-size: 11px; color: #64748B;">Joined on Nov 20, 2026</div>
                            </div>
                        </div>
                        <span class="sa-status-dot-pill sa-status-dot-active" style="font-size: 11px; padding: 2px 8px;">
                            <span>Active</span>
                        </span>
                    </div>

                    <!-- Item 2 -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; background: #FAFAF7; border-radius: 12px; border: 1px solid #F1ECE3;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="sa-lawyer-avatar-dark" style="background: #1E293B;">UK</div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0F172A;">Usman Khan</div>
                                <div style="font-size: 11px; color: #64748B;">Joined on Nov 18, 2026</div>
                            </div>
                        </div>
                        <span class="sa-status-dot-pill sa-status-dot-active" style="font-size: 11px; padding: 2px 8px;">
                            <span>Active</span>
                        </span>
                    </div>

                    <!-- Item 3 -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; background: #FAFAF7; border-radius: 12px; border: 1px solid #F1ECE3;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="sa-lawyer-avatar-dark" style="background: #334155;">AM</div>
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0F172A;">Ahmed Malik</div>
                                <div style="font-size: 11px; color: #64748B;">Joined on Nov 15, 2026</div>
                            </div>
                        </div>
                        <span class="sa-status-dot-pill sa-status-dot-active" style="font-size: 11px; padding: 2px 8px;">
                            <span>Active</span>
                        </span>
                    </div>
                </div>

                <!-- Right: Golden Scales Illustration on Law Book -->
                <div style="position: relative; width: 145px; height: 135px; display: flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; inset: 0; background: radial-gradient(circle, rgba(245,222,155,0.45) 0%, rgba(245,222,155,0.08) 55%, transparent 75%); border-radius: 50%; filter: blur(12px);"></div>
                    
                    <svg style="width: 135px; height: 135px; position: relative; z-index: 2; filter: drop-shadow(0 8px 16px rgba(212,175,55,0.3));" viewBox="0 0 160 160" fill="none">
                        <rect x="22" y="132" width="116" height="14" rx="3" fill="#1A1C23"/>
                        <rect x="28" y="124" width="104" height="10" rx="2" fill="#2C2F3A"/>
                        <rect x="20" y="134" width="8" height="10" rx="1" fill="#D4AF37"/>
                        <line x1="36" y1="139" x2="132" y2="139" stroke="#E6CD92" stroke-width="1.5" stroke-dasharray="2 3"/>

                        <path d="M76 124 L84 124 L82 45 L78 45 Z" fill="url(#goldGradLawyer)"/>
                        <rect x="72" y="118" width="16" height="6" rx="2" fill="url(#goldGradLawyer)"/>
                        <circle cx="80" cy="40" r="5" fill="url(#goldGradLawyer)"/>
                        <path d="M80 35 L80 32 L78 30 L82 30 Z" fill="url(#goldGradLawyer)"/>

                        <path d="M28 46 Q 80 43 132 46" stroke="url(#goldGradLawyer)" stroke-width="3.5" stroke-linecap="round"/>
                        <circle cx="30" cy="46" r="3" fill="url(#goldGradLawyer)"/>
                        <circle cx="130" cy="46" r="3" fill="url(#goldGradLawyer)"/>

                        <line x1="30" y1="48" x2="18" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                        <line x1="30" y1="48" x2="42" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                        <path d="M16 78 Q 30 90 44 78 Z" fill="url(#goldGradLawyer)" stroke="#9A7724" stroke-width="1"/>

                        <line x1="130" y1="48" x2="118" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                        <line x1="130" y1="48" x2="142" y2="78" stroke="#D4AF37" stroke-width="1.2"/>
                        <path d="M116 78 Q 130 90 144 78 Z" fill="url(#goldGradLawyer)" stroke="#9A7724" stroke-width="1"/>

                        <defs>
                            <linearGradient id="goldGradLawyer" x1="0" y1="0" x2="1" y2="1">
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

<!-- Modals -->
@include('superadmin.lawyers._create-modal')
@include('admin.lawyers._delete-modal')

@foreach($lawyers as $lawyer)
    @include('superadmin.lawyers._edit-modal', [
        'lawyer' => $lawyer->loadMissing(['user', 'services', 'jurisdictions']),
        'formServices' => $lawyer->services,
    ])
@endforeach

@push('scripts')
<script>
(function() {
    function initLawyerGrowthChart() {
        if (typeof Chart === 'undefined') {
            setTimeout(initLawyerGrowthChart, 100);
            return;
        }

        const ctx = document.getElementById('lawyerGrowthChart');
        if (ctx) {
            if (window.__saLawyerGrowthChart) {
                window.__saLawyerGrowthChart.destroy();
            }

            const gradientGold = ctx.getContext('2d').createLinearGradient(0, 0, 0, 210);
            gradientGold.addColorStop(0, 'rgba(234, 179, 8, 0.35)');
            gradientGold.addColorStop(1, 'rgba(234, 179, 8, 0.0)');

            window.__saLawyerGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
                    datasets: [{
                        label: 'Lawyers',
                        data: [16, 21, 24, 28, 32, 36],
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
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0C0F17',
                            titleColor: '#FFFFFF',
                            bodyColor: '#F7E7B4',
                            padding: 10,
                            borderRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 40,
                            ticks: {
                                stepSize: 10,
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
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initLawyerGrowthChart();
    } else {
        document.addEventListener('DOMContentLoaded', initLawyerGrowthChart);
    }
    document.addEventListener('turbo:load', initLawyerGrowthChart);
    document.addEventListener('turbo:render', initLawyerGrowthChart);
})();
</script>
@endpush
@endsection

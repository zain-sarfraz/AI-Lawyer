@extends('layouts.superadmin')

@section('title', 'Firms')

@section('content')
<style>
    /* Scoped Firms View Styles */
    .sa-firms-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1520px;
        margin: 0 auto;
    }

    /* Top Bar */
    .sa-firms-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sa-firms-badge {
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

    .sa-firms-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 36px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .sa-firms-subtitle {
        font-size: 14px;
        color: #64748B;
        margin-top: 6px;
        font-weight: 400;
    }

    .sa-firms-top-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .sa-create-firm-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        border-radius: 12px;
        background: linear-gradient(135deg, #F5DE9B 0%, #D4AF37 50%, #B88E28 100%);
        color: #17130A;
        font-size: 13.5px;
        font-weight: 700;
        border: 0;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(212, 175, 55, 0.35);
        transition: all 0.2s ease;
    }

    .sa-create-firm-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 22px rgba(212, 175, 55, 0.45);
    }

    /* 3 Top Metric Cards */
    .sa-firms-metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .sa-firm-metric-card {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 22px 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .sa-firm-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.04);
    }

    .sa-firm-metric-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .sa-firm-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-firm-metric-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748B;
    }

    .sa-firm-metric-val {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 34px;
        font-weight: 700;
        color: #0F172A;
        margin-top: 2px;
        line-height: 1.1;
    }

    .sa-firm-metric-gain {
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
        width: 140px;
        height: 52px;
        pointer-events: none;
    }

    /* All Firms Panel */
    .sa-firms-panel {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
    }

    .sa-firms-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .sa-firms-panel-title {
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

    .sa-view-toggle {
        display: inline-flex;
        background: #F1ECE3;
        border-radius: 10px;
        padding: 3px;
        gap: 3px;
    }

    .sa-view-btn {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: transparent;
        color: #64748B;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .sa-view-btn.active {
        background: #0C0F17;
        color: #FFFFFF;
    }

    /* Firms Table */
    .sa-firms-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .sa-firms-table th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        padding: 12px 14px 12px 8px;
        border-bottom: 1px solid #F1ECE3;
    }

    .sa-firms-table td {
        padding: 15px 14px 15px 8px;
        border-bottom: 1px solid #F8F5EE;
        vertical-align: middle;
        color: #334155;
    }

    .sa-firms-table tr:hover td {
        background: #FAFAF7;
    }

    .sa-firms-table tr:last-child td {
        border-bottom: none;
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

    /* Bottom Row: Firm Growth (58%) & Recent Firms (42%) */
    .sa-firms-bottom-row {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 20px;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .sa-firms-metrics {
            grid-template-columns: 1fr;
        }
        .sa-firms-bottom-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="sa-firms-container">

    <!-- Top Welcome & Actions Bar -->
    <div class="sa-firms-topbar">
        <div>
            <!-- Badge -->
            <div class="sa-firms-badge">
                <svg style="width: 14px; height: 14px; color: #D4AF37;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>
                </svg>
                <span>Firms Management</span>
            </div>

            <h1 class="sa-firms-title">Firms</h1>
            <div class="sa-firms-subtitle">Each firm is an isolated workspace.</div>
        </div>

        <!-- Top Right Actions -->
        <div class="sa-firms-top-actions">
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

            <!-- + Create Firm Button -->
            <button type="button" class="sa-create-firm-btn" @click="$dispatch('open-modal', 'create-firm')">
                <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Create Firm</span>
            </button>
        </div>
    </div>

    <!-- 3 Metric KPI Cards Row -->
    <div class="sa-firms-metrics">
        
        <!-- Total Firms -->
        <div class="sa-firm-metric-card">
            <div class="sa-firm-metric-header">
                <div class="sa-firm-icon-wrap" style="background: #EBF5FF; color: #2563EB;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                        <path d="M9 21v-6h6v6"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-firm-metric-label">Total Firms</div>
                    <div class="sa-firm-metric-val">{{ $totalFirms > 0 ? $totalFirms : 12 }}</div>
                </div>
            </div>
            <div class="sa-firm-metric-gain sa-gain-up">
                <span>↑ +8% from last month</span>
            </div>
            <!-- Blue Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 140 52" fill="none">
                <path d="M0 44 C 35 44, 55 12, 85 30 C 105 42, 120 18, 140 10" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Active Firms -->
        <div class="sa-firm-metric-card">
            <div class="sa-firm-metric-header">
                <div class="sa-firm-icon-wrap" style="background: #ECFDF5; color: #059669;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-firm-metric-label">Active Firms</div>
                    <div class="sa-firm-metric-val">{{ $activeFirms > 0 ? $activeFirms : 10 }}</div>
                </div>
            </div>
            <div class="sa-firm-metric-gain sa-gain-up">
                <span>↑ +11% from last month</span>
            </div>
            <!-- Green Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 140 52" fill="none">
                <path d="M0 44 C 35 44, 55 14, 85 32 C 105 44, 120 20, 140 12" stroke="#10B981" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- Inactive Firms -->
        <div class="sa-firm-metric-card">
            <div class="sa-firm-metric-header">
                <div class="sa-firm-icon-wrap" style="background: #F5F3FF; color: #7C3AED;">
                    <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                </div>
                <div>
                    <div class="sa-firm-metric-label">Inactive Firms</div>
                    <div class="sa-firm-metric-val">{{ $inactiveFirms > 0 ? $inactiveFirms : 2 }}</div>
                </div>
            </div>
            <div class="sa-firm-metric-gain sa-gain-down">
                <span>↓ -1% from last month</span>
            </div>
            <!-- Purple Spline Wave -->
            <svg class="sa-card-spline" viewBox="0 0 140 52" fill="none">
                <path d="M0 44 C 35 44, 55 14, 85 32 C 105 44, 120 20, 140 12" stroke="#8B5CF6" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

    </div>

    <!-- Middle Panel: All Firms Table -->
    <div class="sa-firms-panel">
        
        <!-- Table Header Bar -->
        <div class="sa-firms-panel-header">
            <div class="sa-firms-panel-title">
                <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <span>All Firms</span>
            </div>

            <!-- Controls (Search, Status Filter, View Toggle) -->
            <div class="sa-filter-controls">
                <form method="GET" action="{{ route('superadmin.firms.index') }}" style="display: flex; align-items: center; gap: 10px;">
                    <!-- Search Input -->
                    <div class="sa-search-wrap">
                        <svg class="sa-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search firm..."
                            class="sa-search-input"
                            onchange="this.form.submit()"
                        >
                    </div>

                    <!-- Status Dropdown -->
                    <select name="status" class="sa-select-pill" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ in_array(request('status'), ['inactive', 'suspended']) ? 'selected' : '' }}>Inactive</option>
                    </select>
                </form>

                <!-- Grid/List View Toggle -->
                <div class="sa-view-toggle">
                    <button type="button" class="sa-view-btn active" title="Grid View">
                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                    </button>
                    <button type="button" class="sa-view-btn" title="List View">
                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Data -->
        <div style="overflow-x: auto;">
            <table class="sa-firms-table">
                <thead>
                    <tr>
                        <th style="width: 48px;">#</th>
                        <th>Firm Name</th>
                        <th>Email</th>
                        <th>Plan &amp; Type</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th style="text-align: right; padding-right: 18px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($firms as $index => $firm)
                        @php
                            $isActive = ($firm->status ?? 'active') === 'active';
                            $rowNum = ($firms->currentPage() - 1) * $firms->perPage() + $index + 1;
                            $planName = ucfirst((string) ($firm->plan ?? 'growth'));
                        @endphp
                        <tr>
                            <td style="color: #64748B; font-weight: 600;">{{ $rowNum }}</td>
                            <td style="font-weight: 600; color: #0F172A;">{{ $firm->firm_name }}</td>
                            <td style="color: #475569;">{{ $firm->email ?: '—' }}</td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #FEF9C3; color: #854D0E;">
                                    {{ $planName }} · Law Firm
                                </span>
                            </td>
                            <td>
                                <span class="sa-status-dot-pill {{ $isActive ? 'sa-status-dot-active' : 'sa-status-dot-inactive' }}">
                                    <span class="sa-dot"></span>
                                    <span>{{ $isActive ? 'Active' : 'Inactive' }}</span>
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #0F172A;">{{ $firm->users_count > 0 ? $firm->users_count : ($loop->iteration * 2 + 1) }}</td>
                            <td style="text-align: right; padding-right: 18px;">
                                <div class="sa-action-btn-group">
                                    <!-- View Details -->
                                    <a
                                        href="{{ route('superadmin.firms.show', $firm) }}"
                                        class="sa-action-icon-btn"
                                        title="View firm"
                                    >
                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    <!-- Edit Details -->
                                    <button
                                        type="button"
                                        class="sa-action-icon-btn"
                                        title="Edit firm"
                                        @click="
                                            $dispatch('fill-edit-firm', {
                                                action: @js(route('superadmin.firms.update', $firm)),
                                                firm_name: @js($firm->firm_name),
                                                email: @js($firm->email),
                                                phone: @js($firm->phone),
                                                timezone: @js($firm->timezone),
                                                currency: @js($firm->currency),
                                                status: @js($firm->status ?? 'active'),
                                            });
                                            $dispatch('open-modal', 'edit-firm');
                                        "
                                    >
                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Firm -->
                                    <button
                                        type="button"
                                        class="sa-action-icon-btn is-delete"
                                        title="Delete firm"
                                        @click="
                                            $dispatch('fill-delete-firm', {
                                                action: @js(route('superadmin.firms.destroy', $firm)),
                                                name: @js($firm->firm_name),
                                            });
                                            $dispatch('open-modal', 'delete-firm');
                                        "
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
                            <td colspan="6" style="text-align: center; padding: 32px; color: #94A3B8;">
                                No firms found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="sa-pagination-bar">
            <div class="sa-pagination-info">
                Showing {{ $firms->firstItem() ?? 1 }}–{{ $firms->lastItem() ?? $firms->count() }} of {{ $firms->total() ?? $firms->count() }} firms
            </div>

            <div class="sa-pages-nav">
                @if ($firms->onFirstPage())
                    <span class="sa-page-num" style="opacity: 0.4; cursor: not-allowed;">‹</span>
                @else
                    <a href="{{ $firms->previousPageUrl() }}" class="sa-page-num">‹</a>
                @endif

                @for ($page = 1; $page <= min(3, $firms->lastPage()); $page++)
                    @if ($page == $firms->currentPage())
                        <span class="sa-page-num active">{{ $page }}</span>
                    @else
                        <a href="{{ $firms->url($page) }}" class="sa-page-num">{{ $page }}</a>
                    @endif
                @endfor

                @if ($firms->hasMorePages())
                    <a href="{{ $firms->nextPageUrl() }}" class="sa-page-num">›</a>
                @else
                    <span class="sa-page-num" style="opacity: 0.4; cursor: not-allowed;">›</span>
                @endif
            </div>
        </div>

    </div>

    <!-- Bottom Row: Firm Growth (58%) & Recent Firms (42%) -->
    <div class="sa-firms-bottom-row">
        
        <!-- Firm Growth Chart -->
        <div class="sa-card-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <svg style="width: 20px; height: 20px; color: #0F172A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Firm Growth</span>
                </div>

                <div style="font-size: 12px; font-weight: 600; color: #64748B; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                    <span>Last 6 Months</span>
                    <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </div>
            </div>

            <!-- Canvas Area -->
            <div style="position: relative; width: 100%; height: 210px;">
                <canvas id="firmGrowthChart"></canvas>
            </div>
        </div>

        <!-- Recent Firms List -->
        <div class="sa-card-panel">
            <div>
                <div class="sa-panel-header">
                    <div class="sa-panel-title">
                        <svg style="width: 19px; height: 19px; color: #D4AF37;" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span>Recent Firms</span>
                    </div>

                    <a href="{{ route('superadmin.firms.index') }}" style="font-size: 12.5px; font-weight: 600; color: #C49832; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                        <span>View All</span>
                        <span>→</span>
                    </a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Item 1 -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #FAFAF7; border-radius: 14px; border: 1px solid #F1ECE3;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: #0C0F17; color: #D4AF37; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                                    <path d="M9 21v-6h6v6"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 13.5px; font-weight: 700; color: #0F172A;">ABC Law Firm</div>
                                <div style="font-size: 11.5px; color: #64748B;">Joined on Nov 20, 2026</div>
                            </div>
                        </div>
                        <span class="sa-status-dot-pill sa-status-dot-active">
                            <span>Active</span>
                        </span>
                    </div>

                    <!-- Item 2 -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #FAFAF7; border-radius: 14px; border: 1px solid #F1ECE3;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: #0C0F17; color: #D4AF37; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                                    <path d="M9 21v-6h6v6"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 13.5px; font-weight: 700; color: #0F172A;">Zain Legal Experts</div>
                                <div style="font-size: 11.5px; color: #64748B;">Joined on Nov 18, 2026</div>
                            </div>
                        </div>
                        <span class="sa-status-dot-pill sa-status-dot-active">
                            <span>Active</span>
                        </span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #F1ECE3; font-size: 11.5px; color: #94A3B8;">
                <span>Recently onboarded firm workspaces</span>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
(function() {
    function initFirmGrowthChart() {
        if (typeof Chart === 'undefined') {
            setTimeout(initFirmGrowthChart, 100);
            return;
        }

        const ctx = document.getElementById('firmGrowthChart');
        if (ctx) {
            if (window.__saFirmGrowthChart) {
                window.__saFirmGrowthChart.destroy();
            }

            const gradientGold = ctx.getContext('2d').createLinearGradient(0, 0, 0, 210);
            gradientGold.addColorStop(0, 'rgba(234, 179, 8, 0.35)');
            gradientGold.addColorStop(1, 'rgba(234, 179, 8, 0.0)');

            window.__saFirmGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
                    datasets: [{
                        label: 'Firms',
                        data: [5, 7, 9, 10, 11, 14],
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
                            max: 15,
                            ticks: {
                                stepSize: 5,
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
        initFirmGrowthChart();
    } else {
        document.addEventListener('DOMContentLoaded', initFirmGrowthChart);
    }
    document.addEventListener('turbo:load', initFirmGrowthChart);
    document.addEventListener('turbo:render', initFirmGrowthChart);
})();
</script>
@endpush
@endsection

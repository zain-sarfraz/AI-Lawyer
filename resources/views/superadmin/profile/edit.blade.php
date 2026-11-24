@extends('layouts.superadmin')

@section('title', 'Profile')

@section('content')
@php
    $authUser = auth()->user();
    $userName = $authUser?->name ?: 'Super Admin';
    $userEmail = $authUser?->email ?: 'admin@admin.com';
    $userInitials = strtoupper(substr($userName, 0, 2));
@endphp

<style>
    /* Scoped Profile Page Styles */
    .sa-profile-wrap {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Top Bar */
    .sa-profile-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sa-profile-badge {
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

    .sa-profile-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 36px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .sa-profile-subtitle {
        font-size: 14px;
        color: #64748B;
        margin-top: 6px;
        font-weight: 400;
    }

    /* 2 Column Profile Grid */
    .sa-profile-layout {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 24px;
        align-items: start;
    }

    /* Cards */
    .sa-card-panel {
        background: #FFFFFF;
        border: 1px solid #EBE5DB;
        border-radius: 18px;
        padding: 26px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.015);
    }

    /* Left Profile Summary */
    .sa-profile-summary {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding-bottom: 22px;
        border-bottom: 1px solid #F1ECE3;
    }

    .sa-avatar-lg {
        width: 86px;
        height: 86px;
        border-radius: 50%;
        background: #0C0F17;
        color: #D4AF37;
        font-family: 'Fraunces', serif;
        font-size: 32px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 4px #F5DE9B, 0 8px 24px rgba(212, 175, 55, 0.3);
        margin-bottom: 16px;
    }

    .sa-summary-name {
        font-size: 19px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
    }

    .sa-summary-email {
        font-size: 13px;
        color: #64748B;
        margin-top: 3px;
    }

    .sa-role-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        padding: 4px 12px;
        border-radius: 999px;
        background: #FFF8E8;
        border: 1px solid #F5D998;
        color: #9B6E16;
        font-size: 11px;
        font-weight: 700;
    }

    .sa-meta-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding-top: 20px;
    }

    .sa-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12.5px;
    }

    .sa-meta-key {
        color: #64748B;
        font-weight: 500;
    }

    .sa-meta-val {
        color: #0F172A;
        font-weight: 700;
    }

    /* Right Forms Stack */
    .sa-forms-stack {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .sa-form-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 18px;
        margin-bottom: 22px;
        border-bottom: 1px solid #F1ECE3;
    }

    .sa-form-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #FFF8E8;
        border: 1px solid #F5D998;
        color: #B88E28;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sa-form-header-title {
        font-size: 17px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
    }

    .sa-form-header-desc {
        font-size: 12.5px;
        color: #64748B;
        margin-top: 2px;
    }

    /* Form Fields */
    .sa-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .sa-field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sa-field-label {
        font-size: 13px;
        font-weight: 700;
        color: #1E293B;
    }

    .sa-field-input {
        width: 100%;
        background: #FFFFFF;
        border: 1.5px solid #D5CEBF;
        border-radius: 11px;
        padding: 11px 15px;
        font-size: 14px;
        font-weight: 600;
        color: #0F172A;
        outline: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .sa-field-input:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.22);
        background: #FFFFFF;
    }

    .sa-field-input::placeholder {
        color: #94A3B8;
        font-weight: 400;
    }

    /* Buttons */
    .sa-btn-gold {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 24px;
        border-radius: 12px;
        background: linear-gradient(135deg, #F5DE9B 0%, #D4AF37 50%, #B88E28 100%);
        color: #17130A;
        font-size: 13.5px;
        font-weight: 700;
        border: 0;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35);
        transition: all 0.2s ease;
    }

    .sa-btn-gold:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 22px rgba(212, 175, 55, 0.45);
    }

    .sa-btn-dark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 24px;
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

    .sa-btn-dark:hover {
        background: #1E293B;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(12, 15, 23, 0.35);
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .sa-profile-layout {
            grid-template-columns: 1fr;
        }
        .sa-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="sa-profile-wrap">

    <!-- Top Bar -->
    <div class="sa-profile-topbar">
        <div>
            <!-- Badge -->
            <div class="sa-profile-badge">
                <svg style="width: 14px; height: 14px; color: #D4AF37;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Account Preferences</span>
            </div>

            <h1 class="sa-profile-title">Profile Settings</h1>
            <div class="sa-profile-subtitle">Manage your administrator credentials and platform preferences.</div>
        </div>

        <!-- Top Right Actions -->
        <div style="display: flex; align-items: center; gap: 14px;">
            <!-- Notification Bell -->
            <button type="button" class="sa-notif-btn" aria-label="Notifications">
                <svg style="width: 19px; height: 19px; color: #475569;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="sa-notif-dot"></span>
            </button>

            <!-- User Menu -->
            @include('superadmin.partials.user-header-menu')
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="sa-profile-layout">
        
        <!-- Left: Administrator Summary Card -->
        <div class="sa-card-panel">
            <div class="sa-profile-summary">
                <div class="sa-avatar-lg">{{ $userInitials }}</div>
                <h2 class="sa-summary-name">{{ $userName }}</h2>
                <div class="sa-summary-email">{{ $userEmail }}</div>
                <div class="sa-role-pill">
                    <svg style="width: 12px; height: 12px; color: #D4AF37;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5m14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                    </svg>
                    <span>Platform Superadmin</span>
                </div>
            </div>

            <div class="sa-meta-list">
                <div class="sa-meta-row">
                    <span class="sa-meta-key">Access Level</span>
                    <span class="sa-meta-val" style="color: #059669;">Full Control</span>
                </div>
                <div class="sa-meta-row">
                    <span class="sa-meta-key">Account Status</span>
                    <span class="sa-meta-val" style="display: inline-flex; align-items: center; gap: 5px; color: #065F46; background: #ECFDF5; padding: 2px 8px; border-radius: 999px; font-size: 11px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10B981;"></span>
                        Active
                    </span>
                </div>
                <div class="sa-meta-row">
                    <span class="sa-meta-key">Workspace Scope</span>
                    <span class="sa-meta-val">Global (All Firms)</span>
                </div>
                <div class="sa-meta-row">
                    <span class="sa-meta-key">Jurisdiction Focus</span>
                    <span class="sa-meta-val">Multi-Jurisdiction</span>
                </div>
            </div>
        </div>

        <!-- Right: Forms Stack -->
        <div class="sa-forms-stack">
            
            <!-- Personal Information Form -->
            <div class="sa-card-panel">
                <div class="sa-form-header">
                    <div class="sa-form-header-icon">
                        <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="sa-form-header-title">Personal Details</h2>
                        <div class="sa-form-header-desc">Update your official name and platform login email.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('superadmin.profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="sa-form-grid">
                        <div class="sa-field-group">
                            <label class="sa-field-label" for="profile_name">Full Name *</label>
                            <input
                                id="profile_name"
                                type="text"
                                name="name"
                                class="sa-field-input"
                                value="{{ old('name', $userName) }}"
                                required
                                placeholder="e.g. Zain Sarfraz"
                            >
                        </div>

                        <div class="sa-field-group">
                            <label class="sa-field-label" for="profile_email">Email Address *</label>
                            <input
                                id="profile_email"
                                type="email"
                                name="email"
                                class="sa-field-input"
                                value="{{ old('email', $userEmail) }}"
                                required
                                placeholder="e.g. admin@admin.com"
                            >
                        </div>
                    </div>

                    <div style="margin-top: 22px;">
                        <button type="submit" class="sa-btn-gold">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password & Security Form -->
            <div class="sa-card-panel">
                <div class="sa-form-header">
                    <div class="sa-form-header-icon" style="background: #F1F5F9; border-color: #CBD5E1; color: #475569;">
                        <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="sa-form-header-title">Change Password</h2>
                        <div class="sa-form-header-desc">Ensure your account is protected with a secure password.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('superadmin.profile.password') }}">
                    @csrf
                    @method('PUT')

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="sa-field-group">
                            <label class="sa-field-label" for="current_password">Current Password *</label>
                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                class="sa-field-input"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="sa-form-grid">
                            <div class="sa-field-group">
                                <label class="sa-field-label" for="password">New Password *</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="sa-field-input"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 characters"
                                >
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label" for="password_confirmation">Confirm Password *</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    class="sa-field-input"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Re-type new password"
                                >
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 22px;">
                        <button type="submit" class="sa-btn-dark">
                            <svg style="width: 15px; height: 15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>

</div>
@endsection

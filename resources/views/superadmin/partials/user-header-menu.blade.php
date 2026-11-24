@php
    $authUser = auth()->user();
    $userName = $authUser?->name ?: 'Super Admin';
    $userEmail = $authUser?->email ?: 'admin@admin.com';
    $userInitials = strtoupper(substr($userName, 0, 2));
@endphp

<div class="sa-user-menu-wrapper" x-data="{ open: false }" @click.outside="open = false" style="position: relative; display: inline-block;">
    <!-- Profile Pill Trigger Button -->
    <button
        type="button"
        class="sa-user-pill"
        @click="open = !open"
        :aria-expanded="open"
        aria-label="User menu"
        style="
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #FFFFFF;
            border: 1px solid #E5E0D6;
            border-radius: 999px;
            padding: 5px 14px 5px 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            cursor: pointer;
            transition: all 0.2s ease;
        "
    >
        <div class="sa-user-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: #0C0F17; color: #D4AF37; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
            {{ $userInitials }}
        </div>
        <span class="sa-user-name" style="font-size: 13.5px; font-weight: 600; color: #1E293B; white-space: nowrap;">
            {{ $userName }}
        </span>
        <svg
            style="width: 15px; height: 15px; min-width: 15px; max-width: 15px; color: #94A3B8; transition: transform 0.2s ease;"
            :style="open ? 'transform: rotate(180deg)' : ''"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <!-- Dropdown Modal Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        x-cloak
        style="
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 250px;
            background: #FFFFFF;
            border: 1px solid #EBE5DB;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 12px 36px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
            z-index: 100;
        "
    >
        <!-- Header Info -->
        <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid #F1ECE3;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: #0C0F17; color: #D4AF37; display: flex; align-items: center; justify-content: center; font-size: 13.5px; font-weight: bold; flex-shrink: 0;">
                {{ $userInitials }}
            </div>
            <div style="overflow: hidden; text-align: left;">
                <div style="font-size: 13.5px; font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $userName }}
                </div>
                <div style="font-size: 11.5px; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $userEmail }}
                </div>
                <span style="display: inline-block; margin-top: 4px; padding: 2px 8px; border-radius: 999px; background: #FFF8E8; border: 1px solid #F5D998; color: #9B6E16; font-size: 10px; font-weight: 700;">
                    Superadmin
                </span>
            </div>
        </div>

        <!-- Links List -->
        <div style="display: flex; flex-direction: column; gap: 4px; padding: 8px 0; border-bottom: 1px solid #F1ECE3;">
            <!-- Profile Link (Replaced 'Edit Profile' with 'Profile') -->
            <a
                href="{{ route('superadmin.profile.edit') }}"
                style="display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #334155; text-decoration: none; transition: all 0.15s ease;"
                onmouseover="this.style.background='#F8F6F1'; this.style.color='#0F172A'"
                onmouseout="this.style.background='transparent'; this.style.color='#334155'"
            >
                <svg style="width: 16px; height: 16px; color: #64748B;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Profile</span>
            </a>
        </div>

        <!-- Logout Action -->
        <div style="padding-top: 8px;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    style="
                        width: 100%;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 9px 12px;
                        border-radius: 10px;
                        font-size: 13px;
                        font-weight: 600;
                        color: #DC2626;
                        background: transparent;
                        border: 0;
                        cursor: pointer;
                        text-align: left;
                        transition: all 0.15s ease;
                    "
                    onmouseover="this.style.background='#FEF2F2'"
                    onmouseout="this.style.background='transparent'"
                >
                    <svg style="width: 16px; height: 16px; color: #DC2626;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</div>

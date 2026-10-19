@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5" x-data="superadminDashboard({
    appointments: @js($serializedAppointments),
    adminMetrics: @js($adminMetrics),
    chartData: @js($chartData)
})">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400">SUPERADMIN PLATFORM</div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-(--ink) tracking-tight mt-0.5">Platform Dashboard</h1>
            <p class="text-sm text-(--muted) mt-0.5">Bookings, firms and admins across the whole platform.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 shadow-sm transition"
                    @click="$dispatch('open-modal', 'create-firm')">
                Create firm
            </button>
            <button type="button" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-navy-950 hover:bg-navy-900 shadow-sm transition dark:bg-slate-800 dark:hover:bg-slate-700"
                    @click="$dispatch('open-modal', 'add-admin')">
                Add admin
            </button>
        </div>
    </div>

    {{-- Top 4 KPI Cards Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Appointments --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">APPOINTMENTS</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $stats['total_appointments'] }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <x-ui.icon name="calendar-check" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">{{ $stats['ai_share'] }}% AI booked</div>
        </div>

        {{-- AI Bookings --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">AI BOOKINGS</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $stats['ai_bookings'] }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                    <x-ui.icon name="mic" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Voice receptionist</div>
        </div>

        {{-- Firms --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">FIRMS</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $stats['firms'] }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-slate-500/10 text-slate-600 dark:text-slate-300">
                    <x-ui.icon name="briefcase" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Active workspaces</div>
        </div>

        {{-- Firm Admins --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">FIRM ADMINS</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $stats['firm_admins'] }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-ui.icon name="users" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Owner / admin users</div>
        </div>
    </div>

    {{-- Second Row: Appointment Trend & Status Donut --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Appointment Trend (2 Cols) --}}
        <div class="lg:col-span-2 rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div>
                    <h2 class="font-display text-base font-bold text-(--ink)">Appointment trend</h2>
                    <p class="text-xs text-(--muted) mt-0.5">Past 14 days, AI voice vs staff bookings</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-medium text-(--muted)">
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-teal-600"></span>
                        <span>AI voice</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        <span>Staff</span>
                    </span>
                </div>
            </div>
            <div class="relative w-full h-56 pt-2">
                <canvas id="saTrendChart"></canvas>
            </div>
        </div>

        {{-- Status Donut (1 Col) --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div>
                    <h2 class="font-display text-base font-bold text-(--ink)">Status</h2>
                    <p class="text-xs text-(--muted) mt-0.5">Consultation outcomes</p>
                </div>
                <span class="text-xs font-medium text-(--muted)">{{ $stats['total_appointments'] }} total</span>
            </div>

            {{-- Donut with Center Number --}}
            <div class="relative flex items-center justify-center my-auto py-2">
                <div class="relative h-44 w-44">
                    <canvas id="saStatusChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <span class="font-display text-3xl font-extrabold text-(--ink)">{{ $stats['total_appointments'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Legend List --}}
            <div class="space-y-1.5 pt-3 border-t border-(--line)/60 text-xs">
                <div class="flex items-center justify-between text-(--muted)">
                    <span class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-xs bg-amber-500"></span>
                        <span>Pending</span>
                    </span>
                    <span class="font-bold text-(--ink)">{{ $chartData['status']['values'][2] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-(--muted)">
                    <span class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-xs bg-blue-600"></span>
                        <span>Confirmed</span>
                    </span>
                    <span class="font-bold text-(--ink)">{{ $chartData['status']['values'][0] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-(--muted)">
                    <span class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-xs bg-emerald-600"></span>
                        <span>Completed</span>
                    </span>
                    <span class="font-bold text-(--ink)">{{ $chartData['status']['values'][1] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Third Row: Latest Appointments Panel --}}
    <div id="superadmin-appointments-section" class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="font-display text-base font-bold text-(--ink)">Latest appointments</h2>
                <p class="text-xs text-(--muted) mt-0.5">Across all firms, lawyers and AI receptionist calls</p>
            </div>
            <div class="text-xs text-teal-600 dark:text-teal-400 font-semibold cursor-pointer" @click="resetFilters()">
                <span x-text="'Showing ' + filteredAppointments().length + ' of ' + appointments.length + '.'"></span>
                <span class="underline ml-1">View all</span>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 mb-4">
            {{-- All Firms Dropdown --}}
            <div>
                <select class="w-full rounded-xl border border-(--line) bg-(--canvas) px-3 py-2 text-xs font-medium text-(--ink) focus:outline-none focus:border-teal-500"
                        x-model="filterFirm"
                        aria-label="Filter by firm">
                    <option value="all">All firms</option>
                    @foreach($recentFirms as $f)
                        <option value="{{ $f->id }}">{{ $f->firm_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- All Statuses Dropdown --}}
            <div>
                <select class="w-full rounded-xl border border-(--line) bg-(--canvas) px-3 py-2 text-xs font-medium text-(--ink) focus:outline-none focus:border-teal-500"
                        x-model="filterStatus"
                        aria-label="Filter by status">
                    <option value="all">All statuses</option>
                    <option value="confirmed">Confirmed / Scheduled</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            {{-- All Sources Dropdown --}}
            <div>
                <select class="w-full rounded-xl border border-(--line) bg-(--canvas) px-3 py-2 text-xs font-medium text-(--ink) focus:outline-none focus:border-teal-500"
                        x-model="filterSource"
                        aria-label="Filter by source">
                    <option value="all">All sources</option>
                    <option value="phone_ai">AI receptionist</option>
                    <option value="manual">Staff / manual</option>
                </select>
            </div>

            {{-- Search Input --}}
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-ui.icon name="search" class="h-3.5 w-3.5" />
                </div>
                <input type="text"
                       class="w-full rounded-xl border border-(--line) bg-(--canvas) pl-8 pr-3 py-2 text-xs text-(--ink) focus:outline-none focus:border-teal-500"
                       placeholder="Search client, phone, lawyer"
                       x-model="searchQuery"
                       aria-label="Search appointments">
            </div>
        </div>

        {{-- Appointments Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-(--line) text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3 pr-3">DATE AND TIME</th>
                        <th class="py-3 pr-3">FIRM</th>
                        <th class="py-3 pr-3">CLIENT</th>
                        <th class="py-3 pr-3">LAWYER AND SERVICE</th>
                        <th class="py-3 pr-3">SOURCE</th>
                        <th class="py-3 pr-3">STATUS</th>
                        <th class="py-3 text-right">FEE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-(--line)/60">
                    <template x-for="appt in filteredAppointments()" :key="appt.id">
                        <tr class="hover:bg-slate-500/[0.02] cursor-pointer" @click="openDetails(appt)">
                            {{-- Date and Time --}}
                            <td class="py-3.5 pr-3">
                                <div class="font-bold text-(--ink)" x-text="appt.date"></div>
                                <div class="text-[11px] text-(--muted) mt-0.5" x-text="appt.time + (appt.relative ? ', ' + appt.relative.toLowerCase() : '')"></div>
                            </td>

                            {{-- Firm --}}
                            <td class="py-3.5 pr-3 text-(--ink) font-medium" x-text="appt.firm_name"></td>

                            {{-- Client --}}
                            <td class="py-3.5 pr-3">
                                <div class="font-bold text-(--ink)" x-text="appt.client_name"></div>
                                <div class="text-[11px] text-(--muted) mt-0.5" x-text="appt.client_phone"></div>
                            </td>

                            {{-- Lawyer and Service --}}
                            <td class="py-3.5 pr-3">
                                <div class="font-medium text-(--ink)" x-text="appt.service_name"></div>
                                <div class="text-[11px] text-(--muted) mt-0.5" x-text="'Lawyer: ' + appt.lawyer_name"></div>
                            </td>

                            {{-- Source --}}
                            <td class="py-3.5 pr-3">
                                <template x-if="appt.is_ai">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300">
                                        AI receptionist
                                    </span>
                                </template>
                                <template x-if="!appt.is_ai">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300">
                                        Staff / manual
                                    </span>
                                </template>
                            </td>

                            {{-- Status --}}
                            <td class="py-3.5 pr-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold capitalize"
                                      :class="{
                                          'bg-[#e0e7ff] text-[#2563eb] dark:bg-blue-500/20 dark:text-blue-300': appt.status === 'confirmed' || appt.status === 'scheduled',
                                          'bg-[#fef3c7] text-[#d97706] dark:bg-amber-500/20 dark:text-amber-300': appt.status === 'pending',
                                          'bg-[#dcfce7] text-[#16a34a] dark:bg-emerald-500/20 dark:text-emerald-300': appt.status === 'completed',
                                          'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300': appt.status === 'cancelled' || appt.status === 'no_show',
                                          'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300': appt.status === 'rescheduled'
                                      }"
                                      x-text="appt.status"></span>
                            </td>

                            {{-- Fee --}}
                            <td class="py-3.5 text-right font-bold text-(--ink)" x-text="appt.fee"></td>
                        </tr>
                    </template>

                    <tr x-show="filteredAppointments().length === 0" x-cloak>
                        <td colspan="7" class="py-8 text-center text-(--muted)">
                            No appointments found matching this filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fourth Row: Firm Admins & Recent Firms (2 Columns) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Firm Admins --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display text-base font-bold text-(--ink)">Firm admins</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300">
                        {{ count($adminMetrics) }} active
                    </span>
                </div>

                @forelse($adminMetrics as $adm)
                    @php
                        $initial = mb_strtoupper(mb_substr($adm['name'], 0, 1));
                    @endphp
                    <div class="mb-3">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 shrink-0 grid place-items-center rounded-xl bg-navy-950 text-white font-bold text-sm dark:bg-slate-800">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-sm text-(--ink)">{{ $adm['name'] }}</div>
                                <div class="text-xs text-(--muted)">{{ $adm['email'] }} · Firm {{ $adm['firm_name'] }}</div>
                            </div>
                        </div>

                        {{-- 4-Col Metrics Strip --}}
                        <div class="grid grid-cols-4 gap-2 text-center bg-(--canvas) border border-(--line)/60 rounded-xl p-3 mt-3">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">APPTS</div>
                                <div class="text-base font-extrabold text-(--ink) mt-0.5">{{ $adm['total_appointments'] }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">AI BOOKED</div>
                                <div class="text-base font-extrabold text-(--ink) mt-0.5">{{ $adm['ai_appointments'] }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">LAWYERS</div>
                                <div class="text-base font-extrabold text-(--ink) mt-0.5">{{ $adm['lawyers_count'] }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">EST. FEES</div>
                                <div class="text-base font-extrabold text-(--ink) mt-0.5">${{ number_format($adm['total_fees'], 0) }}</div>
                            </div>
                        </div>

                        <button type="button"
                                class="w-full mt-3 bg-navy-950 hover:bg-navy-900 text-white font-bold text-xs py-2.5 rounded-xl transition dark:bg-slate-800 dark:hover:bg-slate-700"
                                @click="filterByAdmin({{ $adm['admin_id'] }}, '{{ addslashes($adm['name']) }}')">
                            View appointments
                        </button>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-(--muted)">No firm admins found.</div>
                @endforelse
            </div>
        </div>

        {{-- Recent Firms --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display text-base font-bold text-(--ink)">Recent firms</h2>
                    <a href="{{ route('superadmin.firms.index') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        View all firms
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentFirms as $firm)
                        @php
                            $initials = collect(preg_split('/\s+/', trim((string) $firm->firm_name)) ?: [])
                                ->filter()
                                ->map(fn ($p) => mb_substr($p, 0, 1))
                                ->take(2)
                                ->implode('');
                            $initials = $initials !== '' ? strtoupper($initials) : 'F';
                        @endphp
                        <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-500/[0.02]">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-10 w-10 shrink-0 grid place-items-center rounded-full bg-navy-950 text-white font-bold text-xs dark:bg-slate-800">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-(--ink) truncate">{{ $firm->firm_name }}</div>
                                    <div class="text-xs text-(--muted) truncate">
                                        {{ $firm->timezone ?: 'Asia/Karachi' }} · {{ optional($firm->created_at)->format('M j, Y') ?: 'Recent' }}
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300 shrink-0">
                                Active
                            </span>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-(--muted)">No firms found.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Appointment Details Modal --}}
    <x-ui.modal name="sa-appointment-details" title="Appointment Details" subtitle="Comprehensive platform consultation record" maxWidth="2xl">
        <template x-if="selectedAppt">
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3 p-4 rounded-xl border border-(--line) bg-(--canvas)">
                    <div>
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider" x-text="selectedAppt.firm_name"></div>
                        <h3 class="font-display text-xl font-bold text-(--ink) mt-0.5" x-text="selectedAppt.client_name"></h3>
                        <div class="text-sm text-(--muted) mt-1 flex items-center gap-2">
                            <x-ui.icon name="phone" class="h-3.5 w-3.5" />
                            <span x-text="selectedAppt.client_phone"></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold capitalize"
                              :class="{
                                  'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300': selectedAppt.status === 'completed',
                                  'bg-blue-500/15 text-blue-700 dark:text-blue-300': selectedAppt.status === 'confirmed' || selectedAppt.status === 'scheduled',
                                  'bg-amber-500/15 text-amber-700 dark:text-amber-300': selectedAppt.status === 'pending',
                                  'bg-rose-500/15 text-rose-700 dark:text-rose-300': selectedAppt.status === 'cancelled' || selectedAppt.status === 'no_show',
                                  'bg-violet-500/15 text-violet-700 dark:text-violet-300': selectedAppt.status === 'rescheduled'
                              }"
                              x-text="selectedAppt.status"></span>
                        <div class="text-xs text-(--muted) mt-1" x-text="'Booked: ' + selectedAppt.created_at"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Date & Time</div>
                        <div class="text-sm font-bold text-(--ink) mt-1" x-text="selectedAppt.date"></div>
                        <div class="text-xs text-(--muted)" x-text="selectedAppt.time"></div>
                    </div>
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Assigned Lawyer</div>
                        <div class="text-sm font-bold text-(--ink) mt-1" x-text="selectedAppt.lawyer_name"></div>
                        <div class="text-xs text-(--muted)" x-text="selectedAppt.service_name"></div>
                    </div>
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Firm Administrator</div>
                        <div class="text-sm font-bold text-(--ink) mt-1" x-text="selectedAppt.admin_name"></div>
                        <div class="text-xs text-(--muted)" x-text="'Firm: ' + selectedAppt.firm_name"></div>
                    </div>
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Booking Channel</div>
                        <div class="mt-1">
                            <template x-if="selectedAppt.is_ai">
                                <span class="sa-ai-badge">AI Receptionist</span>
                            </template>
                            <template x-if="!selectedAppt.is_ai">
                                <span class="sa-staff-badge">Staff / Manual</span>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Consultation Fee</div>
                        <div class="text-base font-extrabold text-teal-700 dark:text-teal-300 mt-1" x-text="selectedAppt.fee"></div>
                    </div>
                    <div class="p-3 rounded-lg border border-(--line) bg-(--surface)">
                        <div class="text-xs font-semibold text-(--muted) uppercase tracking-wider">Appointment ID</div>
                        <div class="text-sm font-bold text-(--ink) mt-1" x-text="'#' + selectedAppt.id"></div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl border border-(--line) bg-(--surface)">
                    <div class="text-xs font-bold uppercase tracking-wider text-(--muted) mb-1.5">Intake & Consultation Notes</div>
                    <p class="text-sm text-(--ink) whitespace-pre-wrap m-0" x-text="selectedAppt.notes"></p>
                </div>
            </div>
        </template>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
function superadminDashboard(config) {
    return {
        appointments: config.appointments || [],
        adminMetrics: config.adminMetrics || [],
        chartData: config.chartData || {},
        filterFirm: 'all',
        filterStatus: 'all',
        filterSource: 'all',
        searchQuery: '',
        selectedAppt: null,

        init() {
            this.$nextTick(() => {
                this.initCharts();
            });
        },

        filterByAdmin(adminId, adminName) {
            // Find firm_id for this admin
            const admin = this.adminMetrics.find(a => a.admin_id == adminId);
            if (admin) {
                this.filterFirm = admin.firm_id;
            }
            const el = document.getElementById('superadmin-appointments-section');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
            }
        },

        resetFilters() {
            this.filterFirm = 'all';
            this.filterStatus = 'all';
            this.filterSource = 'all';
            this.searchQuery = '';
        },

        filteredAppointments() {
            return this.appointments.filter(appt => {
                if (this.filterFirm !== 'all' && appt.firm_id != this.filterFirm) {
                    return false;
                }

                if (this.filterStatus !== 'all') {
                    if (this.filterStatus === 'confirmed' && !['confirmed', 'scheduled'].includes(appt.status)) {
                        return false;
                    } else if (this.filterStatus === 'cancelled' && !['cancelled', 'no_show'].includes(appt.status)) {
                        return false;
                    } else if (!['confirmed', 'cancelled'].includes(this.filterStatus) && appt.status !== this.filterStatus) {
                        return false;
                    }
                }

                if (this.filterSource !== 'all') {
                    if (this.filterSource === 'phone_ai' && !appt.is_ai) return false;
                    if (this.filterSource === 'manual' && appt.is_ai) return false;
                }

                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase();
                    const matchClient = (appt.client_name || '').toLowerCase().includes(q);
                    const matchPhone = (appt.client_phone || '').toLowerCase().includes(q);
                    const matchLawyer = (appt.lawyer_name || '').toLowerCase().includes(q);
                    const matchService = (appt.service_name || '').toLowerCase().includes(q);
                    const matchFirm = (appt.firm_name || '').toLowerCase().includes(q);
                    if (!matchClient && !matchPhone && !matchLawyer && !matchService && !matchFirm) {
                        return false;
                    }
                }

                return true;
            });
        },

        openDetails(appt) {
            this.selectedAppt = appt;
            this.$dispatch('open-modal', 'sa-appointment-details');
        },

        initCharts() {
            if (typeof Chart === 'undefined') return;

            const isDark = document.documentElement.classList.contains('dark') || localStorage.getItem('theme') === 'dark';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            // 1. Trend Chart
            const trendCanvas = document.getElementById('saTrendChart');
            if (trendCanvas && this.chartData.trend) {
                const trend = this.chartData.trend;
                new Chart(trendCanvas, {
                    type: 'line',
                    data: {
                        labels: trend.labels || [],
                        datasets: [
                            {
                                label: 'AI voice',
                                data: trend.ai || [],
                                borderColor: '#0d9488',
                                backgroundColor: 'transparent',
                                tension: 0.3,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#0d9488',
                                pointRadius: 3,
                            },
                            {
                                label: 'Staff',
                                data: trend.staff || [],
                                borderColor: '#d97706',
                                backgroundColor: 'transparent',
                                tension: 0.3,
                                borderWidth: 2,
                                pointBackgroundColor: '#d97706',
                                pointRadius: 3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { padding: 8, cornerRadius: 8 }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: textColor, font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: textColor, stepSize: 1, font: { size: 11 } }
                            }
                        }
                    }
                });
            }

            // 2. Status Chart (Donut with center text)
            const statusCanvas = document.getElementById('saStatusChart');
            if (statusCanvas && this.chartData.status) {
                const status = this.chartData.status;
                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Confirmed', 'Completed', 'Pending'],
                        datasets: [{
                            data: [
                                status.values[0] || 0,
                                status.values[1] || 0,
                                status.values[2] || 0
                            ],
                            backgroundColor: [
                                '#2563eb', // Confirmed (Blue)
                                '#059669', // Completed (Green)
                                '#d97706', // Pending (Orange)
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '76%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { padding: 8, cornerRadius: 8 }
                        }
                    }
                });
            }
        }
    };
}
</script>
@endpush

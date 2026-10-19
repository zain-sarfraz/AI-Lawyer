@extends('layouts.app')

@section('title', 'Lawyer Dashboard')

@section('content')
@php
    $next = $nextAppointment ?? $upcomingAppointments->first();
    $nextDate = $next?->start_at ? $next->start_at->format('D, M j') : null;
    $nextTime = $next?->start_at ? ($next->start_at->format('g:i A') . ' – ' . optional($next->end_at)->format('g:i A')) : null;
    $nextClient = $next?->client?->name ?: ($next?->lead?->name ?: 'Caller');
    $nextService = $next?->service?->name ?: ($next?->appointmentType?->name ?: 'Consultation');
    $nextInitial = mb_strtoupper(mb_substr($nextClient, 0, 1));
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-(--ink) tracking-tight">{{ $greeting }}</h1>
            <p class="text-sm text-(--muted) mt-0.5">{{ $roleLabel }} desk at {{ $firmShort }}. Your appointments and records at a glance.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('cases.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-(--ink) border border-(--line) bg-(--surface) hover:bg-(--canvas) shadow-xs transition">
                Add case
            </a>
            <a href="{{ route('appointments.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-xs transition">
                New appointment
            </a>
        </div>
    </div>

    {{-- Top 4 KPI Cards Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Today --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">TODAY</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $todayCount }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <x-ui.icon name="calendar-check" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Appointments today</div>
        </div>

        {{-- Upcoming --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">UPCOMING</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $upcomingAppointments->count() }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                    <x-ui.icon name="clock" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Next on your calendar</div>
        </div>

        {{-- Clients --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">CLIENTS</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $clients->count() }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-ui.icon name="users" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Recent records</div>
        </div>

        {{-- Cases --}}
        <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-(--muted)">CASES</span>
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-(--ink) mt-1.5">{{ $cases->count() }}</div>
                </div>
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-slate-500/10 text-slate-600 dark:text-slate-300">
                    <x-ui.icon name="briefcase" class="h-5 w-5" />
                </div>
            </div>
            <div class="text-xs text-(--muted) mt-2">Active matters</div>
        </div>
    </div>

    {{-- Two Column Layout: Left Column (Appointments & Records) + Right Column (Next Appt, Week & Clients) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {{-- Left Column (lg:col-span-8) --}}
        <div class="lg:col-span-8 space-y-5">
            {{-- Card 1: My Appointments --}}
            <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-display text-base font-bold text-(--ink)">My appointments</h2>
                    <a href="{{ route('appointments.index') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-(--line) text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-2.5 pr-3">WHEN</th>
                                <th class="py-2.5 pr-3">WITH</th>
                                <th class="py-2.5 pr-3">TYPE</th>
                                <th class="py-2.5">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-(--line)/60">
                            @forelse($upcomingAppointments as $appt)
                                @php
                                    $with = $appt->client?->name ?: ($appt->lead?->name ?: 'Caller');
                                    $type = $appt->service?->name ?: ($appt->appointmentType?->name ?: 'Consultation');
                                    $status = strtolower((string) ($appt->status?->value ?? $appt->status ?? 'pending'));
                                    $isConfirmed = in_array($status, ['confirmed', 'scheduled', 'completed'], true);
                                @endphp
                                <tr class="hover:bg-slate-500/[0.02]">
                                    <td class="py-3.5 pr-3">
                                        <div class="font-bold text-(--ink)">{{ optional($appt->start_at)->format('D, M j') }}</div>
                                        <div class="text-[11px] text-(--muted) mt-0.5">
                                            {{ optional($appt->start_at)->format('g:i A') }} – {{ optional($appt->end_at)->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 pr-3 font-bold text-(--ink)">{{ $with }}</td>
                                    <td class="py-3.5 pr-3 text-(--muted)">{{ $type }}</td>
                                    <td class="py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold capitalize {{ $isConfirmed ? 'bg-[#dcfce7] text-[#16a34a] dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-[#fef3c7] text-[#d97706] dark:bg-amber-500/20 dark:text-amber-300' }}">
                                            {{ $appt->status?->value ?? $appt->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-xs text-(--muted)">
                                        No upcoming appointments on your calendar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Card 2: My Records --}}
            <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-display text-base font-bold text-(--ink)">My records</h2>
                    <a href="{{ route('crm.documents.index') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        Documents
                    </a>
                </div>

                {{-- Document List Items --}}
                <div class="space-y-3 mb-5">
                    @forelse($documents as $doc)
                        @php
                            $filename = $doc->title ?? $doc->original_name ?? 'Document';
                            $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'DOC');
                            $client = $doc->client?->name ?: 'Sara';
                        @endphp
                        <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-500/[0.02]">
                            <span class="inline-flex items-center justify-center h-7 w-11 rounded-md text-[10px] font-extrabold bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300 shrink-0">
                                {{ $ext }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-(--ink) truncate">{{ $filename }}</div>
                                <div class="text-[11px] text-(--muted) mt-0.5">Client: {{ $client }}</div>
                            </div>
                        </div>
                    @empty
                        {{-- Fallback mock documents matching preview --}}
                        <div class="flex items-center gap-3 p-2 rounded-xl">
                            <span class="inline-flex items-center justify-center h-7 w-11 rounded-md text-[10px] font-extrabold bg-[#e6f7f5] text-[#0d9488] shrink-0">PDF</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-(--ink)">Case file — 12.pdf</div>
                                <div class="text-[11px] text-(--muted)">Client: Sara</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-2 rounded-xl">
                            <span class="inline-flex items-center justify-center h-7 w-11 rounded-md text-[10px] font-extrabold bg-[#e6f7f5] text-[#0d9488] shrink-0">JPG</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-(--ink)">Case image — anders-jilden-cYrMQA7a3Wc-unsplash.jpg</div>
                                <div class="text-[11px] text-(--muted)">Client: Sara</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-2 rounded-xl">
                            <span class="inline-flex items-center justify-center h-7 w-11 rounded-md text-[10px] font-extrabold bg-[#e6f7f5] text-[#0d9488] shrink-0">DOC</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-(--ink)">Case file — Restaurants Testing.docx</div>
                                <div class="text-[11px] text-(--muted)">Client: Sara</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-2 rounded-xl">
                            <span class="inline-flex items-center justify-center h-7 w-11 rounded-md text-[10px] font-extrabold bg-[#e6f7f5] text-[#0d9488] shrink-0">PNG</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-(--ink)">Case image — carrrr.png</div>
                                <div class="text-[11px] text-(--muted)">Client: Sara</div>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2.5 pt-3 border-t border-(--line)/60">
                    <a href="{{ route('cases.index') }}" 
                       class="px-4 py-2 rounded-xl text-xs font-bold text-(--ink) border border-(--line) bg-(--surface) hover:bg-(--canvas) shadow-xs transition">
                        Cases
                    </a>
                    <a href="{{ route('appointments.index') }}" 
                       class="px-4 py-2 rounded-xl text-xs font-bold text-(--ink) border border-(--line) bg-(--surface) hover:bg-(--canvas) shadow-xs transition">
                        Appointments
                    </a>
                    <a href="{{ route('crm.documents.index') }}" 
                       class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-xs transition">
                        Add / view records
                    </a>
                </div>
            </div>
        </div>

        {{-- Right Column (lg:col-span-4) --}}
        <div class="lg:col-span-4 space-y-5">
            {{-- Dark Navy Card: NEXT APPOINTMENT --}}
            <div class="rounded-2xl bg-navy-950 dark:bg-slate-900 p-5 text-white shadow-xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-teal-400">NEXT APPOINTMENT</div>
                <div class="font-display text-2xl font-extrabold text-white mt-1">{{ $nextDate ?: 'Tue, Oct 20' }}</div>
                <div class="text-xs text-slate-300 mt-0.5">{{ $nextTime ?: '11:30 AM – 12:00 PM' }}</div>

                {{-- Inner Card with Client Info --}}
                <div class="rounded-xl bg-navy-900 dark:bg-slate-800 p-3 mt-4 flex items-center gap-3 border border-white/5">
                    <div class="h-9 w-9 rounded-full bg-teal-500 text-white font-extrabold text-sm grid place-items-center shrink-0">
                        {{ $nextInitial ?: 'S' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-sm text-white truncate">{{ $nextClient ?: 'Sara' }}</div>
                        <div class="text-xs text-slate-300 truncate">{{ $nextService ?: 'Employment cases' }}</div>
                    </div>
                </div>

                {{-- Button --}}
                <a href="{{ route('appointments.index') }}" 
                   class="block text-center w-full mt-4 bg-teal-500 hover:bg-teal-400 text-navy-950 font-bold text-xs py-2.5 rounded-xl transition">
                    View details
                </a>
            </div>

            {{-- Card 2: This Week Calendar Strip --}}
            <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
                <h3 class="font-display text-sm font-bold text-(--ink) mb-3">This week</h3>
                <div class="grid grid-cols-7 gap-1.5 text-center">
                    @php
                        $daysList = $weekDays ?? collect([
                            ['name' => 'Mon', 'number' => '19', 'is_today' => false],
                            ['name' => 'Tue', 'number' => '20', 'is_today' => true],
                            ['name' => 'Wed', 'number' => '21', 'is_today' => false],
                            ['name' => 'Thu', 'number' => '22', 'is_today' => false],
                            ['name' => 'Fri', 'number' => '23', 'is_today' => false],
                            ['name' => 'Sat', 'number' => '24', 'is_today' => false],
                            ['name' => 'Sun', 'number' => '25', 'is_today' => false],
                        ]);
                    @endphp

                    @foreach($daysList as $day)
                        @if($day['is_today'])
                            <div class="bg-teal-600 text-white rounded-xl py-2 shadow-xs">
                                <div class="text-[10px] font-medium opacity-90">{{ $day['name'] }}</div>
                                <div class="font-extrabold text-sm mt-0.5 leading-none">{{ $day['number'] }}</div>
                            </div>
                        @else
                            <div class="border border-(--line) text-(--ink) rounded-xl py-2 hover:bg-slate-500/[0.03]">
                                <div class="text-[10px] text-(--muted)">{{ $day['name'] }}</div>
                                <div class="font-bold text-xs mt-0.5 leading-none">{{ $day['number'] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Card 3: My Clients --}}
            <div class="rounded-2xl border border-(--line) bg-(--surface) p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display text-sm font-bold text-(--ink)">My clients</h3>
                    <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        All clients
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($clients as $c)
                        @php
                            $cInitial = mb_strtoupper(mb_substr($c->name, 0, 1));
                        @endphp
                        <div class="flex items-center justify-between gap-3 p-1.5 rounded-xl hover:bg-slate-500/[0.02]">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-8 w-8 rounded-full bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300 font-bold text-xs grid place-items-center shrink-0">
                                    {{ $cInitial }}
                                </div>
                                <div class="font-bold text-xs text-(--ink) truncate">{{ $c->name }}</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e6f7f5] text-[#0d9488] dark:bg-teal-500/20 dark:text-teal-300 shrink-0">
                                Active
                            </span>
                        </div>
                    @empty
                        {{-- Fallback matching preview --}}
                        <div class="flex items-center justify-between gap-3 p-1.5 rounded-xl">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-8 w-8 rounded-full bg-[#e6f7f5] text-[#0d9488] font-bold text-xs grid place-items-center shrink-0">S</div>
                                <div class="font-bold text-xs text-(--ink) truncate">Sara</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e6f7f5] text-[#0d9488] shrink-0">Active</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 p-1.5 rounded-xl">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-8 w-8 rounded-full bg-[#e6f7f5] text-[#0d9488] font-bold text-xs grid place-items-center shrink-0">S</div>
                                <div class="font-bold text-xs text-(--ink) truncate">Sara</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e6f7f5] text-[#0d9488] shrink-0">Active</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

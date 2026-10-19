@extends('layouts.app')

@section('content')
@php
    $calendarBoot = [
        'view' => in_array($filters['view'] ?? 'week', ['day', 'week', 'month'], true) ? $filters['view'] : 'week',
        'date' => $filters['date'] ?? now()->toDateString(),
        'serviceId' => $filters['service_id'] !== null && $filters['service_id'] !== '' ? (int) $filters['service_id'] : '',
        'lawyerId' => $filters['lawyer_id'] !== null && $filters['lawyer_id'] !== '' ? (int) $filters['lawyer_id'] : '',
        'appointments' => $appointments,
        'lawyers' => $lawyers->map(fn ($l) => ['id' => $l->id, 'name' => $l->displayName()])->values(),
        'services' => $services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ];
@endphp

<div x-data="appointmentCalendar(@js($calendarBoot))">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <div class="cal-title-icon">
                <x-ui.icon name="calendar" class="h-5 w-5" />
            </div>
            <div>
                <h1 class="font-display text-3xl leading-tight">Calendar</h1>
                <p class="mt-1 text-sm text-(--muted)">See scheduled appointments for every AI Lawyer counsel.</p>
            </div>
        </div>
        <div class="cal-view-toggle" role="tablist" aria-label="Calendar view">
            <button type="button" :class="view === 'day' && 'is-active'" @click="setView('day')">Day</button>
            <button type="button" :class="view === 'week' && 'is-active'" @click="setView('week')">Week</button>
            <button type="button" :class="view === 'month' && 'is-active'" @click="setView('month')">Month</button>
        </div>
    </div>

    <div class="cal-toolbar-bar mb-4">
        <div class="cal-range">
            <button type="button" class="cal-nav-btn" @click="shift(-1)" aria-label="Previous">
                <x-ui.icon name="chevron-left" />
            </button>
            <div class="cal-range-label" x-text="heading"></div>
            <button type="button" class="cal-nav-btn" @click="shift(1)" aria-label="Next">
                <x-ui.icon name="chevron-right" />
            </button>
        </div>

        <div class="cal-filters">
            <select class="cal-filter" x-model="serviceId">
                <option value="">All services</option>
                <template x-for="service in services" :key="service.id">
                    <option :value="service.id" x-text="service.name"></option>
                </template>
            </select>
            <select class="cal-filter" x-model="lawyerId">
                <option value="">All lawyers</option>
                <template x-for="lawyer in lawyers" :key="lawyer.id">
                    <option :value="lawyer.id" x-text="lawyer.name"></option>
                </template>
            </select>
            <label class="cal-date-btn" title="Jump to date">
                <x-ui.icon name="calendar" />
                <input type="date" x-model="date" @change="syncCursor()">
            </label>
        </div>
    </div>

    <x-legal.card>

        <div x-show="view === 'week'" x-cloak>
            <div class="cal-week-board">
                <template x-for="day in weekDays" :key="day.key">
                    <div class="cal-board-day" :class="{ 'is-today': day.isToday, 'is-empty': day.events.length === 0 }">
                        <div class="cal-board-head">
                            <div class="cal-board-label" x-text="day.label"></div>
                            <div class="cal-board-num" x-text="day.day"></div>
                            <div class="cal-board-count" x-text="day.events.length ? (day.events.length + (day.events.length === 1 ? ' appointment' : ' appointments')) : 'No appointments'"></div>
                        </div>
                        <div class="cal-board-body">
                            <template x-for="event in day.events" :key="event.id">
                                <button type="button" class="cal-chip" :class="['is-' + event.tone, event.is_completed ? 'is-completed' : '']" @click="selected = event">
                                    <span class="cal-chip-dot"></span>
                                    <span class="cal-chip-time" x-text="event.time"></span>
                                    <span class="cal-chip-service" x-text="event.service || event.title"></span>
                                    <span class="cal-chip-lawyer" x-text="event.lawyer || ''"></span>
                                    <span class="cal-chip-status" x-text="event.status_label || event.status"></span>
                                </button>
                            </template>
                            <div class="cal-board-empty" x-show="day.events.length === 0">
                                <x-ui.icon name="calendar" class="h-5 w-5" />
                                <span>No scheduled appointments</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="view === 'month'" x-cloak>
            <div class="cal-weekdays">
                <template x-for="day in weekdays" :key="day">
                    <div class="cal-weekday" x-text="day"></div>
                </template>
            </div>
            <div class="cal-month-grid">
                <template x-for="cell in monthCells" :key="cell.key">
                    <div class="cal-day-cell" :class="{ 'is-muted': !cell.inMonth, 'is-today': cell.isToday }">
                        <div class="cal-day-num" x-text="cell.day"></div>
                        <div class="cal-events">
                            <template x-for="event in cell.events.slice(0, 3)" :key="event.id">
                                <button type="button" class="cal-chip is-compact" :class="'is-' + event.tone" @click="selected = event">
                                    <span class="cal-chip-dot"></span>
                                    <span class="cal-chip-time" x-text="event.time"></span>
                                    <span class="cal-chip-service" x-text="event.service || event.title"></span>
                                </button>
                            </template>
                            <div class="cal-more" x-show="cell.events.length > 3" x-text="'+' + (cell.events.length - 3) + ' more'"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="view === 'day'" x-cloak>
            <div class="cal-day-list">
                <template x-for="event in dayEvents" :key="event.id">
                    <button type="button" class="cal-day-row" @click="selected = event">
                        <div class="cal-day-time" x-text="event.time"></div>
                        <div class="flex-grow-1 text-start">
                            <div class="fw-semibold" x-text="event.service || event.title"></div>
                            <div class="small ui-muted">
                                <span x-text="event.title"></span>
                                ·
                                <span x-text="event.lawyer || 'Lawyer TBD'"></span>
                            </div>
                        </div>
                        <span class="cal-status" x-text="event.status_label || event.status"></span>
                    </button>
                </template>
                <div class="cal-board-empty py-5" x-show="dayEvents.length === 0">
                    <x-ui.icon name="calendar" class="h-6 w-6" />
                    <span>No scheduled appointments</span>
                </div>
            </div>
        </div>

        <div x-show="selected" x-cloak class="cal-detail mt-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="flex-grow-1 min-w-0">
                    <div class="studio-kicker">Appointment</div>
                    <div class="fw-bold text-lg" x-text="selected?.title"></div>
                    <div class="small ui-muted mt-1">
                        <span x-text="selected?.date"></span>
                        ·
                        <span x-text="selected?.time"></span>
                    </div>
                    <div class="small mt-2">
                        <div><strong>Service:</strong> <span x-text="selected?.service || '—'"></span></div>
                        <div><strong>Lawyer:</strong> <span x-text="selected?.lawyer || '—'"></span></div>
                        <div><strong>Status:</strong> <span x-text="selected?.status_label || selected?.status || '—'"></span></div>
                    </div>
                </div>
                <button type="button" class="cal-detail-close" @click="selected = null" aria-label="Close">Close</button>
            </div>
        </div>
    </x-legal.card>
</div>

@endsection

@push('scripts')
<script>
function appointmentCalendar(boot) {
    return {
        view: boot.view || 'week',
        date: boot.date,
        serviceId: boot.serviceId === '' || boot.serviceId === null ? '' : String(boot.serviceId),
        lawyerId: boot.lawyerId === '' || boot.lawyerId === null ? '' : String(boot.lawyerId),
        appointments: boot.appointments || [],
        lawyers: boot.lawyers || [],
        services: boot.services || [],
        selected: null,
        weekdays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        cursor: new Date((boot.date || new Date().toISOString().slice(0, 10)) + 'T12:00:00'),

        get filtered() {
            return this.appointments.filter((event) => {
                if (this.serviceId && String(event.service_id) !== String(this.serviceId)) return false;
                if (this.lawyerId && String(event.lawyer_id) !== String(this.lawyerId)) return false;
                return true;
            });
        },

        get heading() {
            if (this.view === 'week') {
                const start = this.startOfWeek(this.cursor);
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                return start.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
                    + ' – '
                    + end.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            }
            if (this.view === 'day') {
                return this.cursor.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            }
            return this.cursor.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        },

        get monthCells() {
            const year = this.cursor.getFullYear();
            const month = this.cursor.getMonth();
            const first = new Date(year, month, 1);
            const start = this.startOfWeek(first);
            const today = new Date();
            const cells = [];
            for (let i = 0; i < 42; i++) {
                const d = new Date(start);
                d.setDate(start.getDate() + i);
                const key = this.iso(d);
                cells.push({
                    key,
                    day: d.getDate(),
                    inMonth: d.getMonth() === month,
                    isToday: this.iso(d) === this.iso(today),
                    events: this.filtered.filter((e) => e.date === key),
                });
            }
            return cells;
        },

        get weekDays() {
            const start = this.startOfWeek(this.cursor);
            const today = this.iso(new Date());
            // Full week Mon–Sun (weekends included — not off).
            return Array.from({ length: 7 }, (_, i) => {
                const d = new Date(start);
                d.setDate(start.getDate() + i);
                const key = this.iso(d);
                return {
                    key,
                    day: d.getDate(),
                    label: d.toLocaleDateString(undefined, { weekday: 'short' }),
                    isToday: key === today,
                    events: this.filtered.filter((e) => e.date === key),
                };
            });
        },

        get dayEvents() {
            const key = this.iso(this.cursor);
            return this.filtered.filter((e) => e.date === key);
        },

        setView(view) {
            this.view = view;
        },

        syncCursor() {
            if (!this.date) return;
            this.cursor = new Date(this.date + 'T12:00:00');
        },

        shift(step) {
            const d = new Date(this.cursor);
            if (this.view === 'month') d.setMonth(d.getMonth() + step);
            else if (this.view === 'week') d.setDate(d.getDate() + (step * 7));
            else d.setDate(d.getDate() + step);
            this.cursor = d;
            this.date = this.iso(d);
        },

        startOfWeek(date) {
            const d = new Date(date);
            d.setHours(12, 0, 0, 0);
            const day = d.getDay();
            const diff = day === 0 ? -6 : 1 - day;
            d.setDate(d.getDate() + diff);
            return d;
        },

        iso(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },
    };
}
</script>
@endpush

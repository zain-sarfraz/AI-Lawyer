@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="d-flex align-items-center gap-2">
        <div class="cal-title-icon">
            <x-ui.icon name="calendar-check" class="h-5 w-5" />
        </div>
        <div>
            <h1 class="font-display text-3xl leading-tight">Appointments</h1>
            <p class="mt-1 text-sm text-(--muted)">Verified consultations across the AI Lawyer calendar.</p>
        </div>
    </div>
    @can('appointments.manage')
        <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-appointment')">Book appointment</button>
    @endcan
</div>

<form method="GET" action="{{ route('appointments.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="ui-label">Status</label>
        <select name="status" class="ui-field" onchange="this.form.submit()">
            <option value="active" @selected(($statusFilter ?? 'active') === 'active')>Active only</option>
            @foreach(['pending', 'confirmed', 'completed', 'no_show', 'cancelled', 'rescheduled'] as $statusOption)
                <option value="{{ $statusOption }}" @selected(($statusFilter ?? 'active') === $statusOption)>
                    {{ str_replace('_', ' ', $statusOption) }}
                </option>
            @endforeach
        </select>
    </div>
    @if(request('date'))
        <input type="hidden" name="date" value="{{ request('date') }}">
    @endif
</form>

<x-legal.card>
    <div class="ui-table-wrap">
        <table class="ui-table appt-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Service</th>
                    @if($showLawyerColumn ?? true)
                        <th>Lawyer</th>
                    @endif
                    <th>Source</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($appointments as $appointment)
                @php
                    $name = $appointment->client?->name
                        ?: $appointment->lead?->name
                        ?: $appointment->lead?->phone
                        ?: '—';
                    $status = $appointment->status?->value ?? (string) $appointment->status;
                    $canAct = ($canChangeAppointmentStatus ?? false)
                        && in_array($status, ['pending', 'confirmed', 'completed', 'no_show'], true);
                    $sourceLabel = $appointment->source === 'phone_ai' ? 'AI booked' : ($appointment->source ?: 'staff');
                    $sourceTone = $appointment->source === 'phone_ai' ? 'teal' : 'info';
                    $statusClass = match ($status) {
                        'confirmed' => 'is-confirmed',
                        'completed' => 'is-completed',
                        'cancelled', 'no_show' => 'is-cancelled',
                        'pending' => 'is-pending',
                        default => 'is-default',
                    };
                    $call = $relatedCalls->get($appointment->id);
                    $detail = \App\Http\Controllers\Admin\AppointmentController::detailPayload($appointment, $call);
                    $statusOptions = [
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'no_show' => 'No show',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $name }}</td>
                    <td>{{ $appointment->service?->name ?: '—' }}</td>
                    @if($showLawyerColumn ?? true)
                        <td>{{ $appointment->lawyer?->displayName() ?: '—' }}</td>
                    @endif
                    <td>
                        <x-legal.badge :tone="$sourceTone">{{ $sourceLabel }}</x-legal.badge>
                    </td>
                    <td>
                        <button
                            type="button"
                            class="appt-date-link"
                            title="View details"
                            @click="$store.appointmentUi.openDetails(@js($detail))"
                        >
                            {{ $detail['date'] ?? ($appointment->start_at?->format('Y-m-d') ?: '—') }}
                        </button>
                    </td>
                    <td>{{ $detail['time'] ?? ($appointment->start_at?->format('g:i A') ?: '—') }}</td>
                    <td>
                        <div
                            class="appt-status-cell"
                            x-data="{
                                open: false,
                                menuStyle: '',
                                toggle() {
                                    this.open = !this.open;
                                    if (this.open) this.$nextTick(() => this.reposition());
                                },
                                reposition() {
                                    const el = this.$refs.trigger;
                                    if (!el) return;
                                    const r = el.getBoundingClientRect();
                                    const width = 176;
                                    let left = Math.min(Math.max(8, r.right - width), window.innerWidth - width - 8);
                                    const openUp = (window.innerHeight - r.bottom) < 130;
                                    this.menuStyle = openUp
                                        ? `position:fixed;z-index:110;left:${left}px;bottom:${window.innerHeight - r.top + 6}px;top:auto;right:auto;`
                                        : `position:fixed;z-index:110;left:${left}px;top:${r.bottom + 6}px;right:auto;bottom:auto;`;
                                },
                            }"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            @scroll.window="if (open) reposition()"
                            @resize.window="if (open) reposition()"
                        >
                            <button
                                type="button"
                                x-ref="trigger"
                                class="appt-status-pill {{ $statusClass }} {{ $canAct ? 'is-dropdown' : '' }}"
                                @click="toggle()"
                                @if(! $canAct) disabled @endif
                            >
                                <span>{{ str_replace('_', ' ', $status) }}</span>
                                @if($canAct)
                                    <x-ui.icon name="chevron-down" class="h-3.5 w-3.5" />
                                @endif
                            </button>
                            @if($canAct)
                                <div
                                    x-show="open"
                                    x-cloak
                                    class="appt-actions-menu"
                                    :style="menuStyle"
                                >
                                    @foreach($statusOptions as $value => $label)
                                        @if($value !== $status)
                                            <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $value }}">
                                                <button
                                                    type="submit"
                                                    class="appt-action-item {{ $value === 'cancelled' ? 'is-danger' : ($value === 'completed' ? 'is-success' : '') }}"
                                                >{{ $label }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="table-icon-actions">
                            @if($canManageAppointments ?? false)
                                <button
                                    type="button"
                                    class="table-icon-btn is-edit"
                                    title="Edit appointment"
                                    aria-label="Edit appointment"
                                    @click="$store.appointmentUi.openEdit(@js($detail))"
                                >
                                    <x-ui.icon name="pencil" class="h-4 w-4" />
                                </button>
                            @endif
                            @if($canManageCases)
                                <button
                                    type="button"
                                    class="table-icon-btn is-edit"
                                    title="Add case"
                                    aria-label="Add case from appointment"
                                    @click="$store.caseUi.openFromAppointment(@js($detail))"
                                >
                                    <x-ui.icon name="scales" class="h-4 w-4" />
                                </button>
                            @endif
                            <button
                                type="button"
                                class="table-icon-btn is-edit"
                                title="Details"
                                aria-label="View appointment details"
                                @click="$store.appointmentUi.openDetails(@js($detail))"
                            >
                                <x-ui.icon name="eye" class="h-4 w-4" />
                            </button>
                            @can('appointments.manage')
                                <button
                                    type="button"
                                    class="table-icon-btn is-delete"
                                    title="Delete"
                                    aria-label="Delete appointment"
                                    @click="$store.appointmentUi.askDelete({ name: @js($name), url: @js(route('appointments.destroy', $appointment)) })"
                                >
                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($showLawyerColumn ?? true) ? 8 : 7 }}" class="legal-empty py-5">
                        <div class="mx-auto mb-2 grid h-12 w-12 place-items-center rounded-2xl bg-teal-500/10 text-teal-600">
                            <x-ui.icon name="calendar-check" class="h-5 w-5" />
                        </div>
                        <h3>No appointments yet</h3>
                        <p class="mb-0">Appointments booked by Admin or the AI voice agent will appear here.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $appointments->links() }}</div>
</x-legal.card>

@can('appointments.manage')
    @include('appointments._create-modal')
    @include('appointments._edit-modal')
    @include('appointments._delete-modal')
@endcan
@include('appointments._details-modal')
@if($canManageCases)
    @include('crm.cases._create-modal')
@endif
@endsection

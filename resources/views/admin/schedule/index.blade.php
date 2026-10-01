@extends('layouts.app')

@section('content')
<x-legal.page-header title="Calendar" subtitle="Working hours, holidays, and booking windows" />

<div class="row g-3">
    <div class="col-lg-6">
        <x-legal.card title="Working hours">
            <form method="POST" action="{{ route('schedule.working-hours.store') }}">
                @csrf
                <div class="legal-form-section">
                    <h3>Add hours</h3>
                    <p>Firm defaults apply when no lawyer-specific hours exist.</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="ui-label">Lawyer</label>
                            <select name="lawyer_id" class="ui-field">
                                <option value="">Firm default</option>
                                @foreach($lawyers as $lawyer)
                                    <option value="{{ $lawyer->id }}">{{ $lawyer->displayName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Day</label>
                            <select name="day_of_week" class="ui-field">
                                @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $d)
                                    <option value="{{ $i }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">Start</label>
                            <input type="time" name="start_time" class="ui-field" value="09:00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ui-label">End</label>
                            <input type="time" name="end_time" class="ui-field" value="17:00" required>
                        </div>
                    </div>
                </div>
                <button class="ui-btn ui-btn-primary">Save hours</button>
            </form>

            <hr class="my-4" style="border-color:var(--line);opacity:1">

            <ul class="list-unstyled mb-0">
                @forelse($workingHours as $hour)
                    <li class="legal-list-item">
                        <div>
                            <div class="fw-semibold">{{ $hour->lawyer?->displayName() ?: 'Firm default' }}</div>
                            <div class="small ui-muted">Day {{ $hour->day_of_week }}</div>
                        </div>
                        <div class="fw-semibold">{{ $hour->start_time }} – {{ $hour->end_time }}</div>
                    </li>
                @empty
                    <li class="legal-empty py-3">No working hours configured.</li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>

    <div class="col-lg-6">
        <x-legal.card title="Holidays">
            <form method="POST" action="{{ route('schedule.holidays.store') }}">
                @csrf
                <div class="legal-form-section">
                    <h3>Block a date</h3>
                    <p>Holidays close booking for the firm calendar.</p>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="ui-label">Date</label>
                            <input type="date" name="date" class="ui-field" required>
                        </div>
                        <div class="col-md-7">
                            <label class="ui-label">Name</label>
                            <input name="name" class="ui-field" placeholder="Holiday name" required>
                        </div>
                    </div>
                </div>
                <button class="ui-btn ui-btn-primary">Save holiday</button>
            </form>

            <hr class="my-4" style="border-color:var(--line);opacity:1">

            <ul class="list-unstyled mb-0">
                @forelse($holidays as $holiday)
                    <li class="legal-list-item">
                        <div class="fw-semibold">{{ $holiday->name }}</div>
                        <div class="ui-muted">{{ $holiday->date->toDateString() }}</div>
                    </li>
                @empty
                    <li class="legal-empty py-3">No holidays scheduled.</li>
                @endforelse
            </ul>
        </x-legal.card>
    </div>
</div>
@endsection

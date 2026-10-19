@extends('layouts.app')

@section('title', 'Availability')

@section('content')
<div
    x-data="lawyerAvailability(@js([
        'lawyerId' => $lawyer->id,
        'week' => $week,
        'slotMinutes' => $slotMinutes,
    ]))"
>
    <x-legal.page-header
        title="Availability"
        :subtitle="'Set which days and times '.$lawyer->displayName().' is bookable. Callers get '.$slotMinutes.'-minute slots inside these windows.'"
    >
        <x-slot:actions>
            <a href="{{ route('schedule.index') }}" class="ui-btn ui-btn-ghost">Calendar</a>
        </x-slot:actions>
    </x-legal.page-header>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('schedule.availability.sync') }}" class="avail-panel">
        @csrf
        <input type="hidden" name="lawyer_id" :value="lawyerId">

        @unless($restricted)
            <div class="avail-toolbar">
                <label class="avail-lawyer-pick">
                    <span>Lawyer</span>
                    <select
                        class="cal-filter"
                        x-model="lawyerId"
                        @change="window.location = @js(route('schedule.availability')).replace(/\/$/, '') + '?lawyer_id=' + lawyerId"
                    >
                        @foreach($lawyers as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $lawyer->id)>
                                {{ $option->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <p class="avail-hint">Slots are always {{ $slotMinutes }} minutes. Turn a day on, then set start and end.</p>
            </div>
        @else
            <p class="avail-hint mb-4">Your weekly schedule — the phone agent only offers these days and times.</p>
        @endunless

        <div class="avail-day-list">
            <template x-for="(day, index) in week" :key="day.day_of_week">
                <div class="avail-day-row" :class="day.enabled && 'is-on'">
                    <label class="avail-day-toggle">
                        <input type="hidden" :name="'days[' + index + '][day_of_week]'" :value="day.day_of_week">
                        <input type="hidden" :name="'days[' + index + '][enabled]'" :value="day.enabled ? 1 : 0">
                        <input type="checkbox" x-model="day.enabled">
                        <span x-text="day.weekday"></span>
                    </label>
                    <div class="avail-day-times" x-show="day.enabled" x-cloak>
                        <label>
                            <span>From</span>
                            <input type="time" step="1800" x-model="day.start_time" :name="'days[' + index + '][start_time]'" :required="day.enabled">
                        </label>
                        <label>
                            <span>To</span>
                            <input type="time" step="1800" x-model="day.end_time" :name="'days[' + index + '][end_time]'" :required="day.enabled">
                        </label>
                    </div>
                    <div class="avail-day-off" x-show="!day.enabled" x-cloak>Off — not bookable</div>
                </div>
            </template>
        </div>

        <div class="avail-actions">
            <button type="submit" class="ui-btn ui-btn-primary">Save availability</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function lawyerAvailability(boot) {
    return {
        lawyerId: String(boot.lawyerId),
        week: (boot.week || []).map((d) => ({
            day_of_week: d.day_of_week,
            weekday: d.weekday,
            enabled: !!d.enabled,
            start_time: d.start_time || '09:00',
            end_time: d.end_time || '17:00',
        })),
        slotMinutes: boot.slotMinutes || 30,
    };
}
</script>
@endpush

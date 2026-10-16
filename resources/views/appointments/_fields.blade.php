@php
    $idPrefix = $idPrefix ?? 'appt';
    $appointment = $appointment ?? null;
    $defaultName = old('name', $appointment?->client?->name ?: $appointment?->lead?->name);
    $defaultPhone = old('phone', $appointment?->lead?->phone ?: $appointment?->client?->phone);
    $defaultEmail = old('email', $appointment?->lead?->email ?: $appointment?->client?->email);
    $defaultServiceId = old('service_id', $appointment?->service_id);
    $defaultLawyerId = old('lawyer_id', $appointment?->lawyer_id);
    $defaultDate = old('date', $appointment?->start_at?->toDateString() ?: now()->toDateString());
    $defaultNotes = old('notes', $appointment?->notes);
    $defaultStatus = old('status', $appointment?->status?->value ?? 'pending');
    $showStatus = $showStatus ?? false;
@endphp

<input type="hidden" name="_modal" value="{{ $modalName ?? '' }}">

<div class="legal-form-section">
    <div class="row g-3">
        <div class="col-12">
            <label class="ui-label">Name</label>
            <input name="name" class="ui-field" value="{{ $defaultName }}">
        </div>
        <div class="col-12">
            <label class="ui-label">Phone</label>
            <input name="phone" class="ui-field" value="{{ $defaultPhone }}">
        </div>
        <div class="col-12">
            <label class="ui-label">Email</label>
            <input type="email" name="email" class="ui-field" value="{{ $defaultEmail }}">
        </div>
    </div>
</div>

<div class="legal-form-section" data-appt-slot-form="{{ $idPrefix }}">
    <h3>Schedule</h3>
    <p>Slots load from conflict-safe availability.</p>
    <div class="row g-3">
        <div class="col-12">
            <label class="ui-label">Service</label>
            <select name="service_id" id="{{ $idPrefix }}_service_id" class="ui-field" required data-slot-source>
                <option value="">Select service</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" @selected((string) $defaultServiceId === (string) $service->id)>{{ $service->name }} ({{ $service->duration_minutes }} min · {{ $service->currency }} {{ $service->price }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="ui-label">Lawyer</label>
            <select name="lawyer_id" id="{{ $idPrefix }}_lawyer_id" class="ui-field" required data-slot-source>
                <option value="">Select lawyer</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected((string) $defaultLawyerId === (string) $lawyer->id)>{{ $lawyer->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="ui-label">Date</label>
            <input type="date" id="{{ $idPrefix }}_date" class="ui-field" value="{{ $defaultDate }}" data-slot-source>
        </div>
        <div class="col-12">
            <label class="ui-label">Start time</label>
            <select name="start_at" id="{{ $idPrefix }}_start_at" class="ui-field" required data-current-start="{{ $appointment?->start_at?->toIso8601String() }}">
                <option value="">Load slots…</option>
            </select>
        </div>
        @if($showStatus)
            <div class="col-12">
                <label class="ui-label">Status</label>
                <select name="status" class="ui-field">
                    @foreach(['pending', 'confirmed', 'completed', 'no_show', 'cancelled'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected($defaultStatus === $statusOption)>{{ str_replace('_', ' ', $statusOption) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</div>

<div class="legal-form-section !border-0 !pb-0 !mb-0">
    <div class="row g-3">
        <div class="col-12">
            <label class="ui-label">Notes</label>
            <textarea name="notes" class="ui-field" rows="3">{{ $defaultNotes }}</textarea>
        </div>
    </div>
</div>

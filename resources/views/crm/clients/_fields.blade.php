@php
    $idPrefix = $idPrefix ?? 'client';
    $selectedServices = $selectedServices ?? [];
@endphp

<input type="hidden" name="_modal" value="{{ $modalName ?? '' }}">

<div class="legal-form-section !border-0 !pb-0 !mb-0">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="ui-label">Name</label>
            <input name="name" class="ui-field" value="{{ old('name', $client->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="ui-label">Client number</label>
            <input name="client_number" class="ui-field" value="{{ old('client_number', $client->client_number) }}" placeholder="Auto-generated if empty">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Phone number</label>
            <input name="phone" class="ui-field" value="{{ old('phone', $client->phone) }}">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Email</label>
            <input type="email" name="email" class="ui-field" value="{{ old('email', $client->email) }}">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Status</label>
            <select name="status" class="ui-field">
                <option value="active" @selected(old('status', $client->status?->value ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $client->status?->value) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-12">
            <label class="ui-label">Services</label>
            <div class="row g-2">
                @forelse($services as $service)
                    <div class="col-md-6">
                        <div class="ui-check">
                            <input class="ui-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="{{ $idPrefix }}_svc_{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $selectedServices)))>
                            <label class="ui-check-label" for="{{ $idPrefix }}_svc_{{ $service->id }}">{{ $service->name }}</label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 small ui-muted">No services yet. Add them under Services first.</div>
                @endforelse
            </div>
        </div>
        <div class="col-12">
            <label class="ui-label">Notes</label>
            <textarea name="notes" class="ui-field" rows="4" placeholder="Case notes, preferences, follow-up reminders…">{{ old('notes', $client->notes) }}</textarea>
        </div>
    </div>
</div>

<input type="hidden" name="_modal" value="{{ $modalName ?? '' }}">

<div class="legal-form-section">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="ui-label">Name</label>
            <input name="name" value="{{ old('name', $lead->name) }}" class="ui-field" placeholder="Full name">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Phone</label>
            <input name="phone" value="{{ old('phone', $lead->phone) }}" class="ui-field" placeholder="+1…">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Email</label>
            <input name="email" type="email" value="{{ old('email', $lead->email) }}" class="ui-field">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Source</label>
            <input name="source" value="{{ old('source', $lead->source) }}" class="ui-field" placeholder="phone_ai, website, referral…">
        </div>
    </div>
</div>

<div class="legal-form-section !border-0 !pb-0 !mb-0">
    <h3>Pipeline</h3>
    <p>Status, service interest, and counsel assignment.</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="ui-label">Status</label>
            <select name="status" class="ui-field" required>
                @foreach(['new','contacted','qualified','consultation_booked','consultation_completed','converted','lost','follow_up'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $lead->status?->value ?? $lead->status ?? 'new') === $status)>
                        {{ str_replace('_', ' ', $status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="ui-label">Service</label>
            <select name="service_id" class="ui-field">
                <option value="">—</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" @selected(old('service_id', $lead->service_id) == $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="ui-label">Lawyer</label>
            <select name="lawyer_id" class="ui-field">
                <option value="">—</option>
                @foreach($lawyers as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected(old('lawyer_id', $lead->lawyer_id) == $lawyer->id)>{{ $lawyer->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="ui-label">Notes</label>
            <textarea name="notes" class="ui-field" rows="3">{{ old('notes', $lead->notes) }}</textarea>
        </div>
    </div>
</div>

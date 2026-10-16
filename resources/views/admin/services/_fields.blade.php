@php
    $idPrefix = $idPrefix ?? 'service';
    $selectedLawyers = $selectedLawyers ?? [];
@endphp

<input type="hidden" name="_modal" value="{{ $modalName ?? '' }}">

<div class="legal-form-section">
    <div class="row g-3">
        <div class="col-12">
            <label class="ui-label">Name</label>
            <input name="name" class="ui-field" value="{{ old('name', $service->name) }}" required>
        </div>
        <div class="col-12">
            <label class="ui-label">Description</label>
            <textarea name="description" class="ui-field" rows="3">{{ old('description', $service->description) }}</textarea>
        </div>
    </div>
</div>

<div class="legal-form-section">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="ui-label">Price</label>
            <input type="number" step="0.01" name="price" class="ui-field" value="{{ old('price', $service->price) }}" required>
        </div>
        <div class="col-md-3">
            <label class="ui-label">Currency</label>
            <input name="currency" class="ui-field" value="{{ old('currency', $service->currency) }}" required>
        </div>
        <div class="col-md-3">
            <label class="ui-label">Duration (min)</label>
            <input type="number" name="duration_minutes" class="ui-field" value="{{ old('duration_minutes', $service->duration_minutes) }}" required>
        </div>
        <div class="col-md-3">
            <label class="ui-label">Buffer (min)</label>
            <input type="number" name="buffer_minutes" class="ui-field" value="{{ old('buffer_minutes', $service->buffer_minutes) }}">
        </div>
    </div>
</div>

<div class="legal-form-section !border-0 !pb-0 !mb-0">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="ui-label">Status</label>
            <select name="status" class="ui-field">
                <option value="active" @selected(old('status', $service->status?->value ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $service->status?->value) === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>

@extends('layouts.app')

@section('content')
<x-legal.page-header
    :title="$service->exists ? 'Edit service' : 'New service'"
    subtitle="Pricing, duration, and AI booking configuration"
/>

<x-legal.card>
    <form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}">
        @csrf
        @if($service->exists) @method('PUT') @endif

        <div class="legal-form-section">
            <h3>Service details</h3>
            <p>Client-facing catalog values the AI receptionist can explain.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name', $service->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $service->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $service->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="legal-form-section">
            <h3>Pricing & duration</h3>
            <p>Used for booking slots and AI explanations.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $service->price) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input name="currency" class="form-control" value="{{ old('currency', $service->currency) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duration (min)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $service->duration_minutes) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buffer (min)</label>
                    <input type="number" name="buffer_minutes" class="form-control" value="{{ old('buffer_minutes', $service->buffer_minutes) }}">
                </div>
            </div>
        </div>

        <div class="legal-form-section">
            <h3>Availability</h3>
            <p>Control booking and AI visibility independently.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $service->status?->value ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $service->status?->value) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="booking_enabled" value="1" id="booking_enabled" @checked(old('booking_enabled', $service->booking_enabled))>
                    <label class="form-check-label" for="booking_enabled">Booking enabled</label>
                </div>
                <div class="col-md-4 form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="ai_enabled" value="1" id="ai_enabled" @checked(old('ai_enabled', $service->ai_enabled))>
                    <label class="form-check-label" for="ai_enabled">AI visible</label>
                </div>
                <div class="col-12">
                    <label class="form-label">Assigned lawyers</label>
                    <div class="row g-2">
                        @foreach($lawyers as $lawyer)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="lawyer_ids[]" value="{{ $lawyer->id }}" id="law_{{ $lawyer->id }}" @checked(in_array($lawyer->id, old('lawyer_ids', $selectedLawyers)))>
                                    <label class="form-check-label" for="law_{{ $lawyer->id }}">{{ $lawyer->displayName() }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-legal-primary">Save service</button>
        <a href="{{ route('services.index') }}" class="btn btn-legal-secondary ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

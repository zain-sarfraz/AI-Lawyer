@extends('layouts.app')

@section('content')
<x-legal.page-header
    :title="$lead->exists ? 'Edit lead' : 'New lead'"
    subtitle="Capture contact details and assign follow-up ownership"
/>

<x-legal.card>
    <form method="POST" action="{{ $lead->exists ? route('leads.update', $lead) : route('leads.store') }}">
        @csrf
        @if($lead->exists) @method('PUT') @endif

        <div class="legal-form-section">
            <h3>Contact</h3>
            <p>Primary intake fields used across calls and appointments.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" value="{{ old('name', $lead->name) }}" class="form-control" placeholder="Full name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input name="phone" value="{{ old('phone', $lead->phone) }}" class="form-control" placeholder="+1…">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" value="{{ old('email', $lead->email) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Source</label>
                    <input name="source" value="{{ old('source', $lead->source) }}" class="form-control" placeholder="phone_ai, website, referral…">
                </div>
            </div>
        </div>

        <div class="legal-form-section">
            <h3>Pipeline</h3>
            <p>Status, service interest, and counsel assignment.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach(['new','contacted','qualified','consultation_booked','consultation_completed','converted','lost','follow_up'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $lead->status?->value ?? $lead->status ?? 'new') === $status)>
                                {{ str_replace('_', ' ', $status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">—</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" @selected(old('service_id', $lead->service_id) == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lawyer</label>
                    <select name="lawyer_id" class="form-select">
                        <option value="">—</option>
                        @foreach($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}" @selected(old('lawyer_id', $lead->lawyer_id) == $lawyer->id)>{{ $lawyer->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes', $lead->notes) }}</textarea>
                </div>
            </div>
        </div>

        <button class="btn btn-legal-primary">Save lead</button>
        <a href="{{ $lead->exists ? route('leads.show', $lead) : route('leads.index') }}" class="btn btn-legal-secondary ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

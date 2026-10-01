@extends('layouts.app')

@section('content')
<x-legal.page-header
    :title="$client->exists ? 'Edit client' : 'New client'"
    subtitle="Formal client record for retained matters"
/>

<x-legal.card>
    <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
        @csrf
        @if($client->exists) @method('PUT') @endif

        <div class="legal-form-section">
            <h3>Contact details</h3>
            <p>Core identity for communications and scheduling.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input name="phone" class="form-control" value="{{ old('phone', $client->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $client->status?->value ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $client->status?->value) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>
        </div>

        <button class="btn btn-legal-primary">Save client</button>
        <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}" class="btn btn-legal-secondary ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

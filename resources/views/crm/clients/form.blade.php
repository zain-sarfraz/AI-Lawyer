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
                    <label class="ui-label">Name</label>
                    <input name="name" class="ui-field" value="{{ old('name', $client->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Phone</label>
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
                    <label class="ui-label">Notes</label>
                    <textarea name="notes" class="ui-field" rows="4">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>
        </div>

        <button class="ui-btn ui-btn-primary">Save client</button>
        <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

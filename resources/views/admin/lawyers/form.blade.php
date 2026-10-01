@extends('layouts.app')

@section('content')
<x-legal.page-header
    :title="$lawyer->exists ? 'Edit lawyer' : 'New lawyer'"
    subtitle="Counsel profile, services, and availability settings"
/>

<x-legal.card>
    <form method="POST" action="{{ $lawyer->exists ? route('lawyers.update', $lawyer) : route('lawyers.store') }}">
        @csrf
        @if($lawyer->exists) @method('PUT') @endif

        @unless($lawyer->exists)
            <div class="legal-form-section">
                <h3>Account</h3>
                <p>Creates a lawyer user for the firm workspace.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ui-label">Name</label>
                        <input name="name" class="ui-field" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ui-label">Email</label>
                        <input type="email" name="email" class="ui-field" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ui-label">Password</label>
                        <input type="password" name="password" class="ui-field" placeholder="Default: password">
                    </div>
                </div>
            </div>
        @endunless

        <div class="legal-form-section">
            <h3>Profile</h3>
            <p>Title, status, and practice services offered.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ui-label">Title</label>
                    <input name="title" class="ui-field" value="{{ old('title', $lawyer->title) }}" placeholder="Partner, Associate…">
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Timezone</label>
                    <input name="timezone" class="ui-field" value="{{ old('timezone', $lawyer->timezone) }}" placeholder="America/New_York">
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Status</label>
                    <select name="status" class="ui-field">
                        <option value="active" @selected(old('status', $lawyer->status?->value ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $lawyer->status?->value) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="ui-label">Bio</label>
                    <textarea name="bio" class="ui-field" rows="3">{{ old('bio', $lawyer->bio) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="ui-label">Services</label>
                    <div class="row g-2">
                        @foreach($services as $service)
                            <div class="col-md-6">
                                <div class="ui-check">
                                    <input class="ui-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="svc_{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $selectedServices)))>
                                    <label class="ui-check-label" for="svc_{{ $service->id }}">{{ $service->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <button class="ui-btn ui-btn-primary">Save lawyer</button>
        <a href="{{ route('lawyers.index') }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

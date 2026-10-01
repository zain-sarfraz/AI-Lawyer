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
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Default: password">
                    </div>
                </div>
            </div>
        @endunless

        <div class="legal-form-section">
            <h3>Profile</h3>
            <p>Title, status, and practice services offered.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" value="{{ old('title', $lawyer->title) }}" placeholder="Partner, Associate…">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input name="timezone" class="form-control" value="{{ old('timezone', $lawyer->timezone) }}" placeholder="America/New_York">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $lawyer->status?->value ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $lawyer->status?->value) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="3">{{ old('bio', $lawyer->bio) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Services</label>
                    <div class="row g-2">
                        @foreach($services as $service)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="svc_{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $selectedServices)))>
                                    <label class="form-check-label" for="svc_{{ $service->id }}">{{ $service->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-legal-primary">Save lawyer</button>
        <a href="{{ route('lawyers.index') }}" class="btn btn-legal-secondary ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

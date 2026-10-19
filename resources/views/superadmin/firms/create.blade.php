@extends('layouts.superadmin')

@section('title', 'Create firm + admin')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Create firm + admin</h1>
    <p class="mt-1 text-(--muted)">Creates a new isolated dashboard for that admin. They cannot see other firms' data.</p>
</section>

<x-legal.card>
    <form method="POST" action="{{ route('superadmin.firms.store') }}" class="space-y-4">
        @csrf

        <h2 class="legal-card-title">Firm workspace</h2>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm name *</label>
                <input class="ui-field" name="firm_name" value="{{ old('firm_name') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm email</label>
                <input class="ui-field" type="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Phone</label>
                <input class="ui-field" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Timezone</label>
                <input class="ui-field" name="timezone" value="{{ old('timezone', 'Asia/Karachi') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Currency</label>
                <input class="ui-field" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3">
            </div>
        </div>

        <h2 class="legal-card-title mt-4">Admin login</h2>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Admin name *</label>
                <input class="ui-field" name="admin_name" value="{{ old('admin_name') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Admin email *</label>
                <input class="ui-field" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Role</label>
                <select class="ui-field" name="admin_role">
                    <option value="Owner" @selected(old('admin_role', 'Owner') === 'Owner')>Owner</option>
                    <option value="Admin" @selected(old('admin_role') === 'Admin')>Admin</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Password *</label>
                <input class="ui-field" type="password" name="admin_password" required minlength="8">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Confirm password *</label>
                <input class="ui-field" type="password" name="admin_password_confirmation" required minlength="8">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="ui-btn ui-btn-brass">Create firm workspace</button>
        </div>
    </form>
</x-legal.card>
@endsection

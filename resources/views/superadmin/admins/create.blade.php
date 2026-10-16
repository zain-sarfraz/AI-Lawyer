@extends('layouts.superadmin')

@section('title', 'Add admin')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Add admin</h1>
    <p class="mt-1 text-(--muted)">Creates a login for an existing firm. That admin cannot see other firms' data.</p>
</section>

@if($firms->isEmpty())
    <x-legal.card>
        <p class="text-(--muted)">No active firms yet. Create a firm first, then add more admins here.</p>
        <a href="{{ route('superadmin.firms.create') }}" class="ui-btn ui-btn-brass mt-3">Create firm + admin</a>
    </x-legal.card>
@else
    <x-legal.card>
        <form method="POST" action="{{ route('superadmin.admins.store') }}" class="space-y-4">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <label class="ui-label">Firm *</label>
                    <select class="ui-field" name="firm_id" required>
                        <option value="">Select firm…</option>
                        @foreach($firms as $firm)
                            <option value="{{ $firm->id }}" @selected((int) old('firm_id', $selectedFirmId) === (int) $firm->id)>
                                {{ $firm->firm_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                        <option value="Admin" @selected(old('admin_role', 'Admin') === 'Admin')>Admin</option>
                        <option value="Owner" @selected(old('admin_role') === 'Owner')>Owner</option>
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

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="ui-btn ui-btn-brass">Add admin</button>
                <a href="{{ route('superadmin.admins.index') }}" class="ui-btn ui-btn-ghost">Cancel</a>
            </div>
        </form>
    </x-legal.card>
@endif
@endsection

@extends('layouts.superadmin')

@section('title', 'Profile')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Profile</h1>
    <p class="mt-1 text-(--muted)">Your Superadmin account details and password.</p>
</section>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <x-legal.card>
            <h2 class="legal-card-title">Account</h2>
            <form method="POST" action="{{ route('superadmin.profile.update') }}" class="mt-3 space-y-3">
                @csrf
                @method('PATCH')
                <div>
                    <label class="ui-label" for="name">Name</label>
                    <input id="name" class="ui-field" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div>
                    <label class="ui-label" for="email">Email</label>
                    <input id="email" class="ui-field" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <button type="submit" class="ui-btn ui-btn-brass">Save profile</button>
            </form>
        </x-legal.card>
    </div>
    <div class="col-12 col-lg-6">
        <x-legal.card>
            <h2 class="legal-card-title">Password</h2>
            <form method="POST" action="{{ route('superadmin.profile.password') }}" class="mt-3 space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="ui-label" for="current_password">Current password</label>
                    <input id="current_password" class="ui-field" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div>
                    <label class="ui-label" for="password">New password</label>
                    <input id="password" class="ui-field" type="password" name="password" required autocomplete="new-password">
                </div>
                <div>
                    <label class="ui-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" class="ui-field" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <button type="submit" class="ui-btn ui-btn-brass">Update password</button>
            </form>
        </x-legal.card>
    </div>
</div>
@endsection

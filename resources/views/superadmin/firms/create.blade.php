@extends('layouts.superadmin')

@section('title', 'Create firm')

@section('content')
<section class="mb-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Create firm</h1>
    <p class="mt-1 text-(--muted)">Creates a new isolated workspace. Add lawyers from the Lawyers page.</p>
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

        <div class="mt-4">
            <button type="submit" class="ui-btn ui-btn-brass">Create firm workspace</button>
        </div>
    </form>
</x-legal.card>
@endsection

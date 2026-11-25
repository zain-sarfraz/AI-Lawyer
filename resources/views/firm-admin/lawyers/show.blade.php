@extends('layouts.app')

@section('title', $member->name)

@section('content')
@php
    $status = $member->status?->value ?? 'inactive';
    $initials = strtoupper(collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
    $cases = $member->lawyer?->legalCases ?? collect();
    $lawyerCountry = strtoupper((string) ($member->lawyer?->country ?? ''));
    $lawyerDial = $lawyerCountry ? \App\Support\Phone\PhoneNormalizer::dialCode($lawyerCountry) : '';
    $lawyerCountryName = $lawyerCountry ? \App\Support\Phone\PhoneNormalizer::countryLabel($lawyerCountry) : '—';
    $assignedJurisdictions = $member->lawyer?->jurisdictions ?? collect();
@endphp

<div class="mb-4">
    <a href="{{ route('firm.lawyers.index') }}" class="text-sm font-semibold text-teal-600 hover:text-teal-500">← Back to lawyers</a>
</div>

<section class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-navy-900/40 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-4">
        <span class="grid h-16 w-16 place-items-center rounded-2xl bg-teal-500/15 text-lg font-bold text-teal-700 dark:text-teal-300">{{ $initials }}</span>
        <div>
            <h1 class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ $member->name }}</h1>
            <p class="text-sm text-slate-500">{{ $member->getRoleNames()->first() ?: 'Lawyer' }} · {{ $firm->firm_name }}</p>
            <div class="mt-2"><x-legal.badge :tone="$status === 'active' ? 'success' : ($status === 'suspended' ? 'danger' : 'soft')">{{ $status }}</x-legal.badge></div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="#edit" class="ui-btn ui-btn-secondary">Edit details</a>
    </div>
</section>

<div class="mb-5 grid gap-4 lg:grid-cols-2">
    <x-legal.card>
        <h2 class="mb-3 font-semibold">Profile</h2>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Full name</dt><dd class="font-medium">{{ $member->name }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $member->email }}</dd></div>
            <div><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $member->lawyer?->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Assigned role</dt><dd class="font-medium">{{ $member->getRoleNames()->first() ?: 'Lawyer' }}</dd></div>
            <div><dt class="text-slate-500">Joining date</dt><dd class="font-medium">{{ optional($member->created_at)->format('M j, Y') }}</dd></div>
            <div><dt class="text-slate-500">Title</dt><dd class="font-medium">{{ $member->lawyer?->title ?: '—' }}</dd></div>
            <div>
                <dt class="text-slate-500">Country Code</dt>
                <dd class="font-medium">
                    @if ($lawyerCountry)
                        {{ $lawyerCountryName }} ({{ $lawyerCountry }}@if($lawyerDial) · {{ $lawyerDial }}@endif)
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">Case Jurisdictions</dt>
                <dd class="font-medium">
                    @if ($assignedJurisdictions->isEmpty())
                        <span class="text-slate-400">None assigned</span>
                    @else
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($assignedJurisdictions as $jur)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-white/10 dark:text-slate-200">{{ $jur->country_code }} · {{ $jur->code }}</span>
                            @endforeach
                        </div>
                    @endif
                </dd>
            </div>
        </dl>
    </x-legal.card>

    <x-legal.card>
        <h2 class="mb-3 font-semibold">Assigned cases</h2>
        @if ($cases->isEmpty())
            <p class="text-sm text-slate-500">No cases assigned to this lawyer yet.</p>
        @else
            <ul class="divide-y divide-slate-100 dark:divide-white/5 text-sm">
                @foreach ($cases->take(8) as $case)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <div>
                            <div class="font-medium">{{ $case->title }}</div>
                            <div class="text-xs text-slate-500">{{ $case->client?->name ?: 'Client' }} · {{ $case->case_number ?: 'No number' }}</div>
                        </div>
                        <a href="{{ route('cases.show', $case) }}" class="text-teal-600 font-semibold">Open</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-legal.card>
</div>

<x-legal.card id="edit">
    <h2 class="mb-3 font-semibold">Edit details</h2>
    <form method="POST" action="{{ route('firm.lawyers.update', $member) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Full name *</label>
                <input class="ui-field" name="name" value="{{ old('name', $member->name) }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Email *</label>
                <input class="ui-field" type="email" name="email" value="{{ old('email', $member->email) }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Title</label>
                <input class="ui-field" name="title" value="{{ old('title', $member->lawyer?->title) }}">
            </div>
            @include('firm-admin.lawyers._practice-fields', [
                'lawyer' => $member->lawyer,
                'activeJurisdictions' => $activeJurisdictions ?? [],
            ])
            <div class="col-12 col-md-6">
                <label class="ui-label">New password</label>
                <input class="ui-field" type="password" name="password" minlength="8">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Confirm password</label>
                <input class="ui-field" type="password" name="password_confirmation" minlength="8">
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="ui-btn ui-btn-primary">Save changes</button>
        </div>
    </form>

    @unless ($member->isFirmAdmin() || $member->id === auth()->id())
        <div class="mt-6 border-t border-slate-200 pt-5 dark:border-white/10">
            <h3 class="mb-2 font-semibold">Account status</h3>
            <p class="mb-3 text-sm text-slate-500">
                Deactivate to block sign-in for this lawyer without deleting their records.
            </p>
            @if ($status === 'active')
                <form method="POST" action="{{ route('firm.lawyers.status', $member) }}" onsubmit="return confirm('Deactivate {{ $member->name }}?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="ui-btn ui-btn-danger">Deactivate account</button>
                </form>
            @else
                <form method="POST" action="{{ route('firm.lawyers.status', $member) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="ui-btn ui-btn-primary">Reactivate account</button>
                </form>
            @endif
        </div>
    @endunless
</x-legal.card>
@endsection

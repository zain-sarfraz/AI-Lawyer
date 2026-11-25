@extends('layouts.app')

@section('content')
<x-legal.page-header title="Profile" subtitle="Account details, practice country, case law jurisdictions, and security" />

<div class="row g-3">
    <div class="col-lg-8">
        @if ($lawyer && ! $canEditPractice)
            @php
                $countryCode = strtoupper((string) ($lawyer->country ?? ''));
                $dialCode = $countryCode ? \App\Support\Phone\PhoneNormalizer::dialCode($countryCode) : '';
                $countryName = $countryCode ? \App\Support\Phone\PhoneNormalizer::countryLabel($countryCode) : 'Not set';
                $assignedJurisdictions = $lawyer->jurisdictions ?? collect();
            @endphp
            <x-legal.card title="Practice Country & Case Law Access" class="mb-3">
                <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                    Managed by your Firm Admin — only your Firm Admin can change your country code or assigned case jurisdictions.
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-2 mb-4">
                    <div>
                        <dt class="text-xs text-slate-500 uppercase tracking-wider">Country Code</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">
                            @if ($countryCode)
                                {{ $countryName }} ({{ $countryCode }}@if($dialCode) · {{ $dialCode }}@endif)
                            @else
                                Not assigned yet
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500 uppercase tracking-wider">Practice Phone</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $lawyer->phone ?: '—' }}</dd>
                    </div>
                </dl>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Cases Shown To You (Assigned Jurisdictions)</div>
                    @if ($assignedJurisdictions->isEmpty())
                        <p class="text-sm text-slate-500 mb-0">No case law jurisdictions assigned yet. Ask your Firm Admin to assign jurisdictions to your profile.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($assignedJurisdictions as $jur)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                                    <span class="font-bold text-teal-700 dark:text-teal-300">{{ $jur->country_code }}</span>
                                    <span>{{ $jur->name }}</span>
                                    <span class="text-slate-400">({{ $jur->code }})</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-legal.card>
        @endif

        <x-legal.card title="Profile information" class="mb-3">
            @include('profile.partials.update-profile-information-form')
        </x-legal.card>
        <x-legal.card title="Update password" class="mb-3">
            @include('profile.partials.update-password-form')
        </x-legal.card>
    </div>
</div>
@endsection

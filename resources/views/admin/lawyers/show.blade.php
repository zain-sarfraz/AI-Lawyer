@extends('layouts.app')

@section('title', $lawyer->displayName())

@section('content')
<x-legal.page-header :title="$lawyer->displayName()" :subtitle="$lawyer->title ?: 'Counsel profile'">
    <x-slot:actions>
        @can('update', $lawyer)
            <a href="{{ route('lawyers.index', ['open' => 'edit-lawyer-'.$lawyer->id]) }}" class="ui-btn ui-btn-ghost">Edit</a>
        @endcan
        <a href="{{ route('lawyers.index') }}" class="ui-btn ui-btn-ghost">Back</a>
    </x-slot:actions>
</x-legal.page-header>

<div class="mb-4 flex flex-wrap gap-2 border-b border-(--line) pb-2">
    <a href="{{ route('lawyers.show', $lawyer) }}"
       class="ui-btn {{ ($tab ?? 'profile') === 'profile' ? 'ui-btn-primary' : 'ui-btn-ghost' }} text-sm">Profile</a>
    <a href="{{ route('lawyers.relevant-cases', $lawyer) }}"
       class="ui-btn {{ ($tab ?? '') === 'cases' ? 'ui-btn-primary' : 'ui-btn-ghost' }} text-sm">Relevant Cases</a>
</div>

@if(($tab ?? 'profile') === 'profile')
    <x-legal.card>
        <dl class="lawyer-detail-list">
            <div><dt>Phone</dt><dd>{{ $lawyer->phone ?: '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $lawyer->user?->email ?: '—' }}</dd></div>
            <div><dt>Phone country</dt><dd>{{ $lawyer->country ?: '—' }}</dd></div>
            <div><dt>Experience</dt><dd>{{ $lawyer->experience ?: '—' }}</dd></div>
            <div><dt>Timezone</dt><dd>{{ $lawyer->timezone ?: '—' }}</dd></div>
            <div>
                <dt>Jurisdictions</dt>
                <dd>
                    @forelse($lawyer->jurisdictions as $j)
                        <span class="mr-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $j->name }}</span>
                    @empty
                        —
                    @endforelse
                </dd>
            </div>
            <div><dt>Services</dt><dd>{{ $lawyer->services->pluck('name')->join(', ') ?: '—' }}</dd></div>
            <div><dt>Bio</dt><dd style="white-space:pre-wrap">{{ $lawyer->bio ?: 'No bio added yet.' }}</dd></div>
        </dl>
    </x-legal.card>
@else
    <x-legal.card class="mb-4">
        <form method="GET" action="{{ route('lawyers.relevant-cases', $lawyer) }}" class="grid gap-3 md:grid-cols-5">
            <div>
                <label class="ui-label">Search</label>
                <input type="search" name="q" class="ui-field" value="{{ $filters['q'] ?? '' }}" placeholder="Title or citation">
            </div>
            <div>
                <label class="ui-label">Jurisdiction</label>
                <select name="jurisdiction_id" class="ui-field">
                    <option value="">All assigned</option>
                    @foreach($lawyer->jurisdictions as $j)
                        <option value="{{ $j->id }}" @selected((string)($filters['jurisdiction_id'] ?? '') === (string)$j->id)>{{ $j->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label">Case type</label>
                <select name="case_type" class="ui-field">
                    <option value="">All types</option>
                    @foreach(($caseTypes ?? []) as $type)
                        <option value="{{ $type }}" @selected(($filters['case_type'] ?? '') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label">Year</label>
                <select name="year" class="ui-field">
                    <option value="">Any year</option>
                    @foreach(($years ?? []) as $year)
                        <option value="{{ $year }}" @selected((string)($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label">Court</label>
                <select name="court_id" class="ui-field">
                    <option value="">Any court</option>
                    @foreach($courts as $court)
                        <option value="{{ $court->id }}" @selected((string)($filters['court_id'] ?? '') === (string)$court->id)>{{ $court->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="ui-label">Outcome</label>
                <select name="disposition" class="ui-field max-w-xs">
                    <option value="">All outcomes</option>
                    @foreach(($dispositions ?? []) as $disposition)
                        <option value="{{ is_string($disposition) ? $disposition : $disposition->value }}" @selected(($filters['disposition'] ?? '') === (is_string($disposition) ? $disposition : $disposition->value))>
                            {{ is_string($disposition) ? $disposition : $disposition->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                <a href="{{ route('lawyers.relevant-cases', $lawyer) }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>
        </form>
    </x-legal.card>

    @if($lawyer->jurisdictions->isEmpty())
        <x-legal.card>
            <div class="legal-empty">
                <h3>No jurisdictions assigned</h3>
                <p>Assign jurisdictions on the lawyer edit form to see relevant case law.</p>
            </div>
        </x-legal.card>
    @else
        <x-legal.card>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Citation</th>
                            <th>Type</th>
                            <th>Court</th>
                            <th>Jurisdiction</th>
                            <th>Date</th>
                            <th>Outcome</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cases as $case)
                            <tr>
                                <td class="font-medium text-(--ink)">{{ $case->title }}</td>
                                <td class="text-(--muted)">{{ $case->citation ?: '—' }}</td>
                                <td>{{ $case->case_type ?: '—' }}</td>
                                <td>{{ $case->court?->name ?: '—' }}</td>
                                <td>{{ $case->jurisdiction?->name ?: '—' }}</td>
                                <td>{{ optional($case->decision_date)->format('Y-m-d') ?: '—' }}</td>
                                <td>
                                    @if($case->dispositionLabel())
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800">{{ $case->dispositionLabel() }}</span>
                                    @elseif($case->ai_status !== 'done')
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Processing</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('lawyers.case-law.show', [$lawyer, $case]) }}" class="text-sm font-semibold text-teal-700 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-(--muted)">No cases in the database yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $cases->links() }}</div>
        </x-legal.card>
    @endif
@endif
@endsection

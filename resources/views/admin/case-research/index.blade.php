@extends('layouts.app')
@section('title', 'Cases')

@php
    $pretty = static function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if ($value === mb_strtoupper($value) && mb_strlen($value) > 3) {
            return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
        }

        return $value;
    };
    $activeFilterCount = collect([
        $filters['q'] ?? null,
        $filters['jurisdiction_id'] ?? null,
        $filters['case_type'] ?? null,
        $filters['year'] ?? null,
        $filters['court_id'] ?? null,
        $filters['disposition'] ?? null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
<div class="case-index">
    <header class="case-index__hero">
        <div class="case-index__hero-copy">
            <p class="case-index__kicker">Legal research</p>
            <h1 class="case-index__title">Cases</h1>
            <p class="case-index__subtitle">
                Browse reported judgments for your jurisdictions. Filters update the list instantly.
            </p>
        </div>
        <div class="case-index__stats">
            <div class="case-index__stat">
                <span class="case-kicker">Results</span>
                <strong>{{ number_format($cases->total()) }}</strong>
            </div>
            <div class="case-index__stat">
                <span class="case-kicker">Jurisdictions</span>
                <strong>{{ $jurisdictions->count() }}</strong>
            </div>
            <div class="case-index__stat">
                <span class="case-kicker">Page</span>
                <strong>{{ $cases->currentPage() }}/{{ max(1, $cases->lastPage()) }}</strong>
            </div>
        </div>
    </header>

    <section class="case-index__filters case-panel">
        <div class="case-panel__head">
            <h2>Filters</h2>
            @if($activeFilterCount > 0)
                <span class="case-chip case-chip--accent">{{ $activeFilterCount }} active</span>
            @endif
        </div>

        <form
            id="case-filters"
            method="GET"
            action="{{ route('case-research.index') }}"
            class="case-index__form"
            data-turbo-action="advance"
        >
            <div class="case-index__form-grid">
                <div class="case-index__field case-index__field--search">
                    <label class="ui-label" for="case-q">Search</label>
                    <input
                        id="case-q"
                        type="search"
                        name="q"
                        class="ui-field"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Title, citation, case number, keywords"
                        oninput="window.__caseFilterSearch && window.__caseFilterSearch(this.form)"
                    >
                </div>
                <div class="case-index__field">
                    <label class="ui-label" for="case-jurisdiction">Jurisdiction</label>
                    <select id="case-jurisdiction" name="jurisdiction_id" class="ui-field" onchange="this.form.requestSubmit()">
                        <option value="">All</option>
                        @foreach($jurisdictions as $j)
                            <option value="{{ $j->id }}" @selected((string)($filters['jurisdiction_id'] ?? '') === (string) $j->id)>{{ $j->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="case-index__field">
                    <label class="ui-label" for="case-type">Case type</label>
                    <select id="case-type" name="case_type" class="ui-field" onchange="this.form.requestSubmit()">
                        <option value="">All types</option>
                        @foreach($caseTypes as $type)
                            <option value="{{ $type }}" @selected(($filters['case_type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="case-index__field">
                    <label class="ui-label" for="case-year">Year</label>
                    <select id="case-year" name="year" class="ui-field" onchange="this.form.requestSubmit()">
                        <option value="">Any year</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected((string)($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="case-index__field">
                    <label class="ui-label" for="case-court">Court</label>
                    <select id="case-court" name="court_id" class="ui-field" onchange="this.form.requestSubmit()">
                        <option value="">Any court</option>
                        @foreach($courts as $court)
                            <option value="{{ $court->id }}" @selected((string)($filters['court_id'] ?? '') === (string) $court->id)>{{ $court->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="case-index__field">
                    <label class="ui-label" for="case-disposition">Disposition</label>
                    <select id="case-disposition" name="disposition" class="ui-field" onchange="this.form.requestSubmit()">
                        <option value="">All</option>
                        @foreach($dispositions as $disposition)
                            <option value="{{ $disposition->value }}" @selected(($filters['disposition'] ?? '') === $disposition->value)>{{ $disposition->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="case-index__form-footer">
                <label class="case-index__check">
                    <input type="hidden" name="hide_demo" value="0">
                    <input type="checkbox" name="hide_demo" value="1" class="ui-check-input" onchange="this.form.requestSubmit()" @checked(!empty($filters['hide_demo']))>
                    Hide demo data
                </label>
                <a href="{{ route('case-research.index') }}" class="ui-btn ui-btn-ghost">Reset filters</a>
            </div>
        </form>
    </section>

    @if($lawyer && $lawyer->jurisdictions->isEmpty())
        <section class="case-panel">
            <div class="legal-empty">
                <h3>No jurisdictions on your profile</h3>
                <p>Ask an admin to assign jurisdictions on your lawyer profile so Cases can load matching judgments.</p>
            </div>
        </section>
    @else
        <section class="case-panel case-index__results">
            <div class="case-index__results-head">
                <p>
                    Showing
                    <strong>{{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ number_format($cases->total()) }}</strong>
                    cases
                </p>
            </div>

            <div class="ui-table-wrap case-index__table-wrap">
                <table class="ui-table case-index__table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Citation</th>
                            <th>Type</th>
                            <th>Court</th>
                            <th>Date</th>
                            <th>Disposition</th>
                            <th>Winner</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cases as $case)
                            <tr>
                                <td>
                                    <div class="case-index__title-cell">
                                        <a href="{{ route('case-research.show', $case) }}" class="case-index__title-link">
                                            {{ $pretty($case->title) }}
                                        </a>
                                        @if($case->is_demo)
                                            <span class="case-chip case-chip--demo">Demo</span>
                                        @endif
                                    </div>
                                    @if($case->jurisdiction?->name)
                                        <div class="case-index__sub">{{ $case->jurisdiction->name }}</div>
                                    @endif
                                </td>
                                <td class="case-index__muted">{{ $case->neutral_citation ?: ($case->citation ?: '—') }}</td>
                                <td>
                                    @if($case->case_type)
                                        <span class="case-chip">{{ ucfirst($case->case_type) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $case->court?->name ?: '—' }}</td>
                                <td>{{ optional($case->decision_date)->format('d M Y') ?: '—' }}</td>
                                <td>
                                    @if($case->dispositionLabel())
                                        <span class="case-chip case-chip--accent">{{ $case->dispositionLabel() }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $case->displayWinnerLabel() }}</td>
                                <td class="case-index__action">
                                    <a href="{{ route('case-research.show', $case) }}" class="case-index__view">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="case-index__empty">
                                        <h3>No cases matched</h3>
                                        <p>Try clearing filters or searching with a different citation / keyword.</p>
                                        <a href="{{ route('case-research.index') }}" class="ui-btn ui-btn-ghost">Reset filters</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cases->hasPages())
                <div class="case-index__pager">{{ $cases->links() }}</div>
            @endif
        </section>
    @endif
</div>
@endsection

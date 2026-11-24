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
            data-case-filters
        >
            {{-- Intentionally omit `page` so filter/search changes always reset to page 1. --}}
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
                    <strong>{{ number_format($cases->firstItem() ?? 0) }}–{{ number_format($cases->lastItem() ?? 0) }}</strong>
                    of
                    <strong>{{ number_format($cases->total()) }}</strong>
                    cases
                </p>
                <p class="case-index__page-size">{{ $cases->perPage() }} per page</p>
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
                                        <h3>No cases have been fetched for this jurisdiction yet</h3>
                                        <p>
                                            Only real judgments with a public source link are shown — never sample data.
                                            @if(!empty($fetchMeta))
                                                @foreach($fetchMeta as $meta)
                                                    <br>{{ $meta['name'] }}: {{ $meta['status'] ?: 'never' }}@if($meta['at']) ({{ $meta['at'] }})@endif
                                                @endforeach
                                            @endif
                                        </p>
                                        <a href="{{ route('case-research.index') }}" class="ui-btn ui-btn-ghost">Reset filters</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cases->total() > 0)
                @php
                    $currentPage = $cases->currentPage();
                    $lastPage = max(1, $cases->lastPage());
                    $window = 2;
                    $pageStart = max(1, $currentPage - $window);
                    $pageEnd = min($lastPage, $currentPage + $window);
                    if ($pageEnd - $pageStart < ($window * 2)) {
                        $pageStart = max(1, $pageEnd - ($window * 2));
                        $pageEnd = min($lastPage, $pageStart + ($window * 2));
                    }
                @endphp
                <nav class="case-index__pager" aria-label="Cases pagination">
                    <p class="case-index__pager-summary">
                        Showing
                        <strong>{{ number_format($cases->firstItem() ?? 0) }}–{{ number_format($cases->lastItem() ?? 0) }}</strong>
                        of
                        <strong>{{ number_format($cases->total()) }}</strong>
                        cases
                    </p>

                    <div class="case-index__pager-nav">
                        @if($cases->onFirstPage())
                            <span class="case-index__page-btn is-disabled" aria-disabled="true">
                                <x-ui.icon name="chevron-left" class="h-4 w-4" />
                                <span>Previous</span>
                            </span>
                        @else
                            <a
                                href="{{ $cases->previousPageUrl() }}"
                                class="case-index__page-btn"
                                rel="prev"
                                data-turbo-action="advance"
                            >
                                <x-ui.icon name="chevron-left" class="h-4 w-4" />
                                <span>Previous</span>
                            </a>
                        @endif

                        <div class="case-index__page-numbers" role="list">
                            @if($pageStart > 1)
                                <a href="{{ $cases->url(1) }}" class="case-index__page-num" data-turbo-action="advance" role="listitem">1</a>
                                @if($pageStart > 2)
                                    <span class="case-index__page-ellipsis" aria-hidden="true">…</span>
                                @endif
                            @endif

                            @for($page = $pageStart; $page <= $pageEnd; $page++)
                                @if($page === $currentPage)
                                    <span class="case-index__page-num is-current" aria-current="page" role="listitem">{{ $page }}</span>
                                @else
                                    <a href="{{ $cases->url($page) }}" class="case-index__page-num" data-turbo-action="advance" role="listitem">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($pageEnd < $lastPage)
                                @if($pageEnd < $lastPage - 1)
                                    <span class="case-index__page-ellipsis" aria-hidden="true">…</span>
                                @endif
                                <a href="{{ $cases->url($lastPage) }}" class="case-index__page-num" data-turbo-action="advance" role="listitem">{{ $lastPage }}</a>
                            @endif
                        </div>

                        @if($cases->hasMorePages())
                            <a
                                href="{{ $cases->nextPageUrl() }}"
                                class="case-index__page-btn"
                                rel="next"
                                data-turbo-action="advance"
                            >
                                <span>Next</span>
                                <x-ui.icon name="chevron-right" class="h-4 w-4" />
                            </a>
                        @else
                            <span class="case-index__page-btn is-disabled" aria-disabled="true">
                                <span>Next</span>
                                <x-ui.icon name="chevron-right" class="h-4 w-4" />
                            </span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    @endif
</div>
@endsection

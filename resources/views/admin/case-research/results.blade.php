@extends('layouts.app')
@section('title', 'Cases — ' . ($categories[$filters['category'] ?? '']['label'] ?? 'Search results'))

@section('content')
@php
    $currentCategory = $filters['category'] ?? '';
    $categoryLabel   = $categories[$currentCategory]['label'] ?? '';
    $routes = [
        'search'  => route('case-research.search'),
        'api'     => route('case-research.api.search'),
        'case'    => url('case-research/api/case'),
        'courts'  => route('case-research.api.courts'),
        'types'   => route('case-research.api.case-types'),
    ];
    $typeValues = array_column($caseTypes, 'value');
    $categoryMap = collect($categories)->mapWithKeys(fn (array $cat, string $key) => [$key => $cat['label']])->all();
@endphp

<div class="cr-page"
     x-data="caseResearch(@js($initialData), @js($routes), @js($filters), @js($typeValues), @js($categoryLabel), @js($categoryMap))"
     x-init="boot()">

    <div class="mb-4 flex items-center gap-2 text-sm">
        <a href="{{ route('case-research.index') }}" class="text-(--muted) hover:text-(--ink) transition" data-nav-prefetch>Cases</a>
        <x-ui.icon name="chevron-right" class="h-3 w-3 text-(--muted)" />
        <span class="font-semibold" x-text="categoryLabel || 'Search results'">{{ $categoryLabel ?: 'Search results' }}</span>
    </div>

    <x-legal.page-header :title="$categoryLabel ?: 'Case search'" subtitle="Records and statistics come from the live provider query — filters change both">
        <x-slot:actions>
            <a href="{{ route('case-research.index') }}" class="ui-btn ui-btn-ghost text-sm" data-nav-prefetch>
                <x-ui.icon name="chevron-left" class="h-4 w-4" />
                All categories
            </a>
        </x-slot:actions>
    </x-legal.page-header>

    @if(! $configured)
        <div class="ui-alert ui-alert-danger mb-4">
            No provider token is configured, so no records can be retrieved. Add
            <code>COURTLISTENER_API_TOKEN</code> to <code>.env</code> and run
            <code>php artisan courtlistener:verify</code>.
        </div>
    @endif

    {{-- ---------------- Filter bar ---------------- --}}
    <x-legal.card class="mb-4">
        <form method="GET" action="{{ route('case-research.search') }}" @submit.prevent="apply(1)" class="cr-filters">

            <div class="cr-filter-group cr-filter-grow">
                <label class="cr-filter-label" for="cr-q">Search terms</label>
                <div class="relative">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-(--muted)" />
                    <input id="cr-q" type="search" class="ui-field pl-9" placeholder="e.g. wrongful dismissal"
                           name="q" x-model="filters.q" @keydown.enter.prevent="apply(1)" autocomplete="off" />
                </div>
            </div>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-category">Category</label>
                <select id="cr-category" class="ui-field" name="category" x-model="filters.category" @change="onCategoryChange()">
                    <option value="">All categories</option>
                    @foreach($categories as $key => $cat)
                        <option value="{{ $key }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Searchable case-type selector: provider list + types found in results --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-type">Case type</label>
                <input id="cr-type" list="cr-type-options" class="ui-field" placeholder="Any type"
                       name="case_type" x-model="filters.case_type" @change="apply(1)" autocomplete="off" />
                <datalist id="cr-type-options">
                    <template x-for="type in typeOptions()" :key="type">
                        <option :value="type"></option>
                    </template>
                </datalist>
            </div>

            {{-- Searchable court selector, fed by the provider's court directory --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-court">Court</label>
                <input id="cr-court" list="cr-court-options" class="ui-field" placeholder="Any court"
                       name="court" x-model="filters.court" @change="apply(1)" @focus="loadCourts()" autocomplete="off" />
                <span class="cr-field-note" x-show="courtsLoading">Loading court directory…</span>
                <span class="cr-field-note" x-show="courts.length === 0 && !courtsLoading && courtsTried">
                    Court directory unavailable right now — you can still type a court ID such as <code>scotus</code>.
                </span>
                <datalist id="cr-court-options">
                    <template x-for="court in courts" :key="court.id">
                        <option :value="court.id" :label="court.name"></option>
                    </template>
                </datalist>
            </div>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-country">Country</label>
                <select id="cr-country" class="ui-field" name="country" x-model="filters.country" @change="apply(1)">
                    <option value="">Any supported country</option>
                    @foreach($countryOptions as $option)
                        <option value="{{ $option['value'] }}">
                            {{ $option['label'] }}{{ $option['supported'] ? '' : ' — no data source yet' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-range">Decision date</label>
                <select id="cr-range" class="ui-field" name="date_range" x-model="filters.date_range" @change="onRangeChange()">
                    @foreach($dateRanges as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <template x-if="filters.date_range === 'custom'">
                <div class="cr-filter-group cr-filter-dates">
                    <label class="cr-filter-label">From — To</label>
                    <div class="flex gap-2">
                        <input type="date" class="ui-field" name="date_from" x-model="filters.date_from" @change="apply(1)" :max="today" />
                        <input type="date" class="ui-field" name="date_to" x-model="filters.date_to" @change="apply(1)" :max="today" />
                    </div>
                </div>
            </template>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="cr-outcome">Outcome</label>
                <select id="cr-outcome" class="ui-field" name="outcome" x-model="filters.outcome" @change="apply(1)">
                    <option value="">Any outcome</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                    <option value="pending">Pending</option>
                    <option value="unknown">Unknown</option>
                </select>
            </div>

            <div class="cr-filter-actions">
                <button type="submit" class="ui-btn ui-btn-primary">
                    <x-ui.icon name="search" class="h-4 w-4" /> Search
                </button>
                <button type="button" class="ui-btn ui-btn-ghost" x-show="hasFilters()" @click="resetFilters()">Reset</button>
            </div>

            {{-- Provider state carried as hidden inputs for no-JS form posts --}}
            <input type="hidden" name="page" :value="filters.page" />
            <input type="hidden" name="per_page" :value="filters.per_page" />
        </form>
    </x-legal.card>

    {{-- ---------------- Notices ---------------- --}}
    <template x-if="notice()">
        <div class="cr-notice mb-4" :class="'is-' + noticeKind()">
            <x-ui.icon name="shield" class="h-4 w-4 shrink-0" />
            <div>
                <strong x-text="noticeTitle()"></strong>
                <span x-text="notice()"></span>
                <strong x-show="data.reason === 'rate_limited' && data.retry_after">
                    Retry in <span x-text="countdown"></span>s.
                </strong>
            </div>
        </div>
    </template>

    {{-- ---------------- Statistics ---------------- --}}
    <div class="cr-stats-row mb-4" x-show="!loading">
        <div class="kpi-card is-blue h-full">
            <div class="kpi-icon"><x-ui.icon name="book-open" class="h-4 w-4" /></div>
            <div class="kpi-label">Records matching filters</div>
            <div class="kpi-value" x-text="fmt(data.total)"></div>
            <div class="kpi-hint" x-text="`${fmt(data.per_page)} shown per page · page ${data.page} of ${Math.max(data.total_pages, 1)}`"></div>
        </div>

        <div class="kpi-card is-mint h-full">
            <div class="kpi-icon"><x-ui.icon name="calendar" class="h-4 w-4" /></div>
            <div class="kpi-label">Decision window</div>
            <div class="kpi-value text-base" x-text="rangeLabel()"></div>
            <div class="kpi-hint" x-text="`${data.range?.date_from || '—'} → ${data.range?.date_to || '—'} (decision dates)`"></div>
        </div>

        <div class="kpi-card is-teal h-full">
            <div class="kpi-icon"><x-ui.icon name="scales" class="h-4 w-4" /></div>
            <div class="kpi-label">Cases analysed</div>
            <div class="kpi-value" x-text="fmt(data.stats?.analyzed ?? 0)"></div>
            <div class="kpi-hint">
                <span x-text="outcomeBreakdown()"></span>
            </div>
        </div>

        <div class="kpi-card is-amber h-full">
            <div class="kpi-icon"><x-ui.icon name="chart" class="h-4 w-4" /></div>
            <div class="kpi-label">Win / loss rate</div>
            <div class="kpi-value" x-text="winLossDisplay()"></div>
            <div class="kpi-hint"
                 x-text="`From ${data.stats?.classifiable ?? 0} verifiable outcome${(data.stats?.classifiable ?? 0) === 1 ? '' : 's'} of ${fmt(data.stats?.analyzed ?? 0)} analysed`"></div>
        </div>
    </div>

    <p class="cr-stats-note" x-show="data.stats?.outcome_note" x-text="data.stats?.outcome_note"></p>

    {{-- ---------------- Loading ---------------- --}}
    <div x-show="loading" class="cr-loading">
        <span class="cr-loading-spinner"></span>
        <p>Querying court records…</p>
    </div>

    {{-- ---------------- Results ---------------- --}}
    <x-legal.card x-show="!loading">
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                <tr>
                    <th>Case &amp; citation</th>
                    <th>Court / jurisdiction</th>
                    <th>Category &amp; type</th>
                    <th>Decision date</th>
                    <th>Outcome</th>
                    <th class="text-end">Source</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="record in data.results" :key="record.case_id || record.case_name">
                    <tr class="cr-result-row" @click="openDetail(record)" style="cursor: pointer">
                        <td class="max-w-sm">
                            <div class="text-sm font-semibold leading-snug" x-text="record.case_name"></div>
                            <div class="cr-cell-sub" x-text="record.citation || record.docket_number || 'No published citation'"></div>
                            <div class="cr-cell-snippet" x-show="record.snippet" x-text="clip(record.snippet)"></div>
                        </td>
                        <td>
                            <div class="text-sm" x-text="record.court_name || record.court_id || '—'"></div>
                            <div class="cr-cell-sub" x-text="courtMeta(record)"></div>
                        </td>
                        <td>
                            <div class="text-sm" x-text="record.category || 'Uncategorised'"></div>
                            <div class="cr-cell-sub" x-text="record.nature_of_suit || 'Type not stated'"></div>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="text-sm" x-text="fmtDate(record.date_filed)"></div>
                            <div class="cr-cell-sub" x-text="record.status || 'Precedential status not stated'"></div>
                        </td>
                        <td>
                            <span class="cr-outcome-pill" :class="'is-' + (record.outcome_class || 'unknown')"
                                  x-text="outcomeLabel(record)"></span>
                            <div class="cr-cell-sub" x-show="record.judgment_text" x-text="clip(record.judgment_text, 70)"></div>
                        </td>
                        <td class="text-end whitespace-nowrap">
                            <a :href="record.url" target="_blank" rel="noopener noreferrer"
                               class="cr-source-link" @click.stop x-show="record.url">
                                <x-ui.icon name="external-link" class="h-3 w-3" /> CourtListener
                            </a>
                            <button type="button" class="cr-source-link is-ghost" @click.stop="openDetail(record)">Details</button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        {{-- Empty states: distinct per provider reason --}}
        <div x-show="!loading && (data.results || []).length === 0" class="legal-empty py-10">
            <h3 x-text="emptyHeading()"></h3>
            <p class="mb-0" x-text="data.message || emptyHint()"></p>
        </div>

        {{-- Pagination --}}
        <div class="cr-pagination" x-show="data.total_pages > 1">
            <a class="ui-btn ui-btn-ghost" :href="pageUrl(data.page - 1)" @click.prevent="goPage(data.page - 1)"
               :class="{ 'is-disabled': !data.has_prev }" :aria-disabled="!data.has_prev">
                <x-ui.icon name="chevron-left" class="h-4 w-4" /> Prev
            </a>
            <span class="text-sm text-(--muted)">
                Page <strong x-text="data.page"></strong> of <strong x-text="data.total_pages"></strong>
                · <span x-text="fmt(data.total)"></span> matching records
            </span>
            <a class="ui-btn ui-btn-ghost" :href="pageUrl(data.page + 1)" @click.prevent="goPage(data.page + 1)"
               :class="{ 'is-disabled': !data.has_next }" :aria-disabled="!data.has_next">
                Next <x-ui.icon name="chevron-right" class="h-4 w-4" />
            </a>
        </div>

        <div class="cr-page-count-note" x-show="data.total_pages >= 100">
            The provider exposes at most 100 pages for one query. Narrow the filters to reach older records.
        </div>
    </x-legal.card>

    {{-- ---------------- Case detail modal ---------------- --}}
    <x-ui.modal name="case-detail" title="Case record" subtitle="Fields below are exactly what the provider publishes" max-width="2xl">
        <div x-show="detailLoading" class="cr-loading">
            <span class="cr-loading-spinner"></span>
            <p>Retrieving the source record…</p>
        </div>

        <template x-if="detailError">
            <div class="ui-alert ui-alert-danger" x-text="detailError"></div>
        </template>

        <div x-show="detail && !detailLoading">
            <h3 class="font-display text-lg text-(--ink)" x-text="detail?.case_name"></h3>
            <dl class="cr-detail-grid">
                <template x-for="row in detailRows()" :key="row.label">
                    <div class="cr-detail-item">
                        <dt x-text="row.label"></dt>
                        <dd x-text="row.value"></dd>
                    </div>
                </template>
            </dl>

            <div class="cr-detail-block" x-show="detail?.snippet">
                <h4>Extract from the decision</h4>
                <p x-text="detail?.snippet"></p>
            </div>

            <div class="cr-detail-block" x-show="detail?.syllabus">
                <h4>Syllabus published by the source</h4>
                <p x-text="detail?.syllabus"></p>
            </div>

            <div class="cr-detail-note">
                <x-ui.icon name="shield" class="h-4 w-4 shrink-0" />
                <span x-text="outcomeNote()"></span>
            </div>

            <button type="button" class="ui-btn ui-btn-ghost mt-4" @click="fetchFullRecord()"
                    :disabled="detailLoading" x-show="/^\d+$/.test(String(detail?.case_id || ''))">
                Load the full source record
            </button>
        </div>

        <x-slot:footer>
            <a class="ui-btn ui-btn-ghost" :href="detail?.url" target="_blank" rel="noopener noreferrer" x-show="detail?.url">
                <x-ui.icon name="external-link" class="h-4 w-4" /> Open on CourtListener
            </a>
            <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('close-modal', 'case-detail')">Close</button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ---------------- Provider footnote ---------------- --}}
    <div class="mt-4 text-center">
        <p class="text-xs text-(--muted)">
            Records from <a href="https://www.courtlistener.com" target="_blank" rel="noopener" class="underline hover:text-(--ink)">CourtListener</a>
            (Free Law Project) — United States federal and state courts only. Statistics use only the records listed above;
            win/loss is reported solely where the source states a party-relative judgment.
        </p>
    </div>
</div>
@endsection

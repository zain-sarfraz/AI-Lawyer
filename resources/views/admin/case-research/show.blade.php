@extends('layouts.app')
@section('title', $record->title)

@php
    $labels = $record->partyLabels();
    $citation = $record->neutral_citation ?: $record->citation;
    $sourceUrl = $record->primarySourceUrl();
    $bench = array_values(array_filter($record->bench_json ?? []));
    $issues = array_values(array_filter($record->legal_issues_json ?? []));
    $laws = array_values(array_filter($record->laws_applied_json ?? []));
    $keywords = array_values(array_filter($record->keywords_json ?? []));
    $cited = array_values(array_filter($record->cited_cases_json ?? []));
    $reasoning = array_values(array_filter($record->court_reasoning_json ?? []));
    $p1Evidence = array_values(array_filter($record->party_one_evidence_json ?? []));
    $p2Evidence = array_values(array_filter($record->party_two_evidence_json ?? []));
    $hasOutcome = filled($record->disposition) || filled($record->final_order) || ($record->winnerEnum()->value !== 'not_stated');
    $hasParties = filled($record->party_one_name) || filled($record->party_two_name) || $bench !== [] || filled($record->author_judge);
    $hasEnrichment = filled($record->ai_summary) || $issues !== [] || $laws !== [] || $reasoning !== [] || $cited !== [] || $p1Evidence !== [] || $p2Evidence !== [];
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
@endphp

@section('content')
<article class="case-detail">
    <div class="case-detail__notice">
        <strong>Verification required.</strong>
        AI summaries and extracted facts must be checked by a qualified lawyer before reliance. Prefer the official report when citing.
    </div>

    <header class="case-detail__hero">
        <div class="case-detail__hero-top">
            <a href="{{ route('case-research.index') }}" class="case-detail__back">← Back to cases</a>
            <div class="case-detail__chips">
                @if($record->case_type)
                    <span class="case-chip">{{ ucfirst($record->case_type) }}</span>
                @endif
                @if($record->dispositionLabel())
                    <span class="case-chip case-chip--accent">{{ $record->dispositionLabel() }}</span>
                @endif
                @if($record->jurisdiction?->name)
                    <span class="case-chip case-chip--muted">{{ $record->jurisdiction->name }}</span>
                @endif
                @if($record->is_demo)
                    <span class="case-chip case-chip--demo">Demo data</span>
                @endif
                @if($record->needsReview())
                    <span class="case-chip case-chip--warn">Needs review</span>
                @endif
            </div>
        </div>

        <h1 class="case-detail__title">{{ $pretty($record->title) }}</h1>

        @if($citation || $record->case_number)
            <p class="case-detail__citation">
                @if($citation)<span>{{ $citation }}</span>@endif
                @if($citation && $record->case_number)<span class="case-detail__dot">·</span>@endif
                @if($record->case_number)<span>{{ $record->case_number }}</span>@endif
            </p>
        @endif

        <dl class="case-detail__meta">
            <div>
                <dt>Court</dt>
                <dd>{{ $record->court?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt>Decision</dt>
                <dd>{{ optional($record->decision_date)->format('d M Y') ?: '—' }}</dd>
            </div>
            <div>
                <dt>Filed</dt>
                <dd>{{ optional($record->filing_date)->format('d M Y') ?: '—' }}</dd>
            </div>
            <div>
                <dt>Source</dt>
                <dd>{{ $record->source ? str_replace('-', ' ', $record->source) : '—' }}</dd>
            </div>
        </dl>

        <div class="case-detail__actions">
            @if($sourceUrl)
                <a href="{{ $sourceUrl }}" target="_blank" rel="noopener" class="ui-btn ui-btn-primary">Open full judgment</a>
            @endif
            @if($record->pdf_url && $record->pdf_url !== $sourceUrl)
                <a href="{{ $record->pdf_url }}" target="_blank" rel="noopener" class="ui-btn ui-btn-ghost">PDF</a>
            @endif
        </div>
    </header>

    <div class="case-detail__layout">
        <div class="case-detail__main">
            @if($hasParties)
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Parties</h2>
                    </div>
                    <div class="case-parties">
                        <div class="case-party">
                            <span class="case-party__role">{{ $labels['party_one'] }}</span>
                            <strong>{{ $pretty($record->party_one_name) ?: 'Not named' }}</strong>
                        </div>
                        <div class="case-parties__vs" aria-hidden="true">v</div>
                        <div class="case-party case-party--right">
                            <span class="case-party__role">{{ $labels['party_two'] }}</span>
                            <strong>{{ $pretty($record->party_two_name) ?: 'Not named' }}</strong>
                        </div>
                    </div>
                    @if($bench !== [] || filled($record->author_judge))
                        <div class="case-bench">
                            @if($bench !== [])
                                <div>
                                    <span class="case-kicker">Bench</span>
                                    <p>{{ implode(' · ', $bench) }}</p>
                                </div>
                            @endif
                            @if(filled($record->author_judge))
                                <div>
                                    <span class="case-kicker">Author</span>
                                    <p>{{ $record->author_judge }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>
            @endif

            <section class="case-panel">
                <div class="case-panel__head">
                    <h2>Lawyers involved</h2>
                    @if(!empty($counsel))
                        <span class="case-chip case-chip--accent">{{ count($counsel) }} counsel</span>
                    @endif
                </div>

                @if(!empty($counsel))
                    <div class="case-counsel">
                        @foreach($counsel as $row)
                            @php
                                $key = mb_strtolower(trim((string) ($row['name'] ?? '')));
                                $firmMatch = $matchedFirmLawyers[$key] ?? null;
                            @endphp
                            <article class="case-counsel__card">
                                <div class="case-counsel__top">
                                    <h3>{{ $row['name'] ?? 'Counsel' }}</h3>
                                    @if(!empty($row['side']))
                                        <span class="case-chip">{{ $row['side'] }}</span>
                                    @endif
                                    @if($firmMatch)
                                        <span class="case-chip case-chip--accent">On your roster</span>
                                    @endif
                                </div>
                                <p class="case-counsel__meta">
                                    {{ ucfirst((string) ($row['role'] ?? 'counsel')) }}
                                    @if(!empty($row['instructed_by']))
                                        · Instructed by {{ $row['instructed_by'] }}
                                    @endif
                                </p>
                                @if($firmMatch)
                                    <a href="{{ route('lawyers.show', $firmMatch) }}" class="case-counsel__link">View firm profile →</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="case-prose case-prose--muted mb-0">
                        Counsel names are not listed for this record yet.
                        @if($sourceUrl)
                            Check the full judgment for advocates / solicitors who appeared.
                        @endif
                    </p>
                @endif
            </section>

            @if($hasOutcome)
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Outcome</h2>
                    </div>
                    <div class="case-outcome">
                        <div class="case-outcome__card">
                            <span class="case-kicker">Winner</span>
                            <p>
                                @if($record->winnerEnum()->value === 'not_stated')
                                    Not stated in judgment
                                @else
                                    {{ $record->displayWinnerLabel() }}
                                @endif
                            </p>
                        </div>
                        <div class="case-outcome__card">
                            <span class="case-kicker">Disposition</span>
                            <p>{{ $record->dispositionLabel() ?: 'Not available' }}</p>
                        </div>
                    </div>
                    @if(filled($record->final_order))
                        <div class="case-order">
                            <span class="case-kicker">Final order</span>
                            <p>{{ $record->final_order }}</p>
                        </div>
                    @endif
                </section>
            @endif

            @if(filled($record->ai_summary))
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Summary</h2>
                    </div>
                    <p class="case-prose">{{ $record->ai_summary }}</p>
                </section>
            @endif

            @if($issues !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Legal questions</h2>
                    </div>
                    <ol class="case-list case-list--numbered">
                        @foreach($issues as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if($laws !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Laws applied</h2>
                    </div>
                    <ul class="case-laws">
                        @foreach($laws as $law)
                            <li>
                                <strong>{{ $law['statute'] ?? '—' }}</strong>
                                @if(!empty($law['section']))
                                    <span class="case-laws__section">{{ $law['section'] }}</span>
                                @endif
                                @if(!empty($law['description']))
                                    <p>{{ $law['description'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($p1Evidence !== [] || $p2Evidence !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Evidence</h2>
                    </div>
                    <div class="case-evidence">
                        <div>
                            <h3>{{ $labels['party_one'] }}</h3>
                            <ul class="case-list">
                                @forelse($p1Evidence as $item)
                                    <li>
                                        <div>{{ $item['point'] ?? '' }}</div>
                                        @if(!empty($item['paragraph_ref']))
                                            <span class="case-ref">{{ $item['paragraph_ref'] }}</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="is-empty">Not available</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <h3>{{ $labels['party_two'] }}</h3>
                            <ul class="case-list">
                                @forelse($p2Evidence as $item)
                                    <li>
                                        <div>{{ $item['point'] ?? '' }}</div>
                                        @if(!empty($item['paragraph_ref']))
                                            <span class="case-ref">{{ $item['paragraph_ref'] }}</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="is-empty">Not available</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </section>
            @endif

            @if($reasoning !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Court’s reasoning</h2>
                    </div>
                    <ul class="case-list">
                        @foreach($reasoning as $item)
                            <li>
                                <div>{{ $item['point'] ?? '' }}</div>
                                @if(!empty($item['paragraph_ref']))
                                    <span class="case-ref">{{ $item['paragraph_ref'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($cited !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Cited cases</h2>
                    </div>
                    <ul class="case-cites">
                        @foreach($cited as $cite)
                            <li>
                                <strong>{{ $cite['title'] ?? 'Untitled' }}</strong>
                                @if(!empty($cite['citation']))
                                    <span>{{ $cite['citation'] }}</span>
                                @endif
                                @if(!empty($cite['context']))
                                    <p>{{ $cite['context'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(! $hasEnrichment)
                <section class="case-panel case-panel--soft">
                    <div class="case-panel__head">
                        <h2>Read the judgment</h2>
                    </div>
                    <p class="case-prose case-prose--muted">
                        Detailed AI extraction is not available for this record yet. Use the official judgment link for the full text, holding, and cited authorities.
                    </p>
                    @if($sourceUrl)
                        <a href="{{ $sourceUrl }}" target="_blank" rel="noopener" class="ui-btn ui-btn-primary mt-3">Open on source site</a>
                    @endif
                </section>
            @endif
        </div>

        <aside class="case-detail__aside">
            <section class="case-panel">
                <div class="case-panel__head">
                    <h2>Case facts</h2>
                </div>
                <dl class="case-facts">
                    <div>
                        <dt>Jurisdiction</dt>
                        <dd>{{ $record->jurisdiction?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Court</dt>
                        <dd>{{ $record->court?->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Type</dt>
                        <dd>{{ $record->case_type ? ucfirst($record->case_type) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>Reported</dt>
                        <dd>{{ is_null($record->is_reported) ? '—' : ($record->is_reported ? 'Yes' : 'No') }}</dd>
                    </div>
                    <div>
                        <dt>External ID</dt>
                        <dd class="case-facts__mono">{{ $record->external_id ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            @if($keywords !== [])
                <section class="case-panel">
                    <div class="case-panel__head">
                        <h2>Keywords</h2>
                    </div>
                    <div class="case-detail__chips">
                        @foreach($keywords as $keyword)
                            <span class="case-chip case-chip--muted">{{ $keyword }}</span>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="case-panel">
                <div class="case-panel__head">
                    <h2>AI status</h2>
                </div>
                <p class="case-prose case-prose--muted mb-0">
                    @if($record->ai_model || $record->ai_extracted_at)
                        Extracted by {{ $record->ai_model ?: 'unknown model' }}
                        @if($record->ai_extracted_at)
                            on {{ $record->ai_extracted_at->timezone(config('app.timezone'))->toDayDateTimeString() }}
                        @endif
                        @if(! is_null($record->ai_confidence))
                            · confidence {{ number_format((float) $record->ai_confidence * 100, 0) }}%
                        @endif
                    @else
                        No AI extraction yet for this judgment.
                    @endif
                </p>
            </section>
        </aside>
    </div>
</article>
@endsection

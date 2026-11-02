<?php

namespace App\Models;

use App\Enums\CaseDisposition;
use App\Enums\CaseWinner;
use App\Support\Legal\CaseLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseLawRecord extends Model
{
    protected $fillable = [
        'jurisdiction_id', 'court_id', 'external_id', 'source', 'title',
        'case_number', 'citation', 'neutral_citation', 'other_citations', 'case_type',
        'party_one_name', 'party_one_role', 'party_two_name', 'party_two_role', 'parties_json',
        'bench_json', 'author_judge', 'counsel_json', 'filing_date', 'decision_date',
        'source_url', 'judgment_url', 'pdf_url', 'is_reported',
        'full_text', 'ai_summary',
        'winner', 'winner_label', 'disposition', 'final_order',
        'laws_applied_json', 'legal_issues_json',
        'party_one_evidence_json', 'party_two_evidence_json',
        'court_reasoning_json', 'cited_cases_json', 'keywords_json', 'topic',
        'evidence_json',
        'ai_status', 'ai_confidence', 'ai_model', 'ai_extracted_at', 'ai_notes',
        'is_demo', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'other_citations' => 'array',
            'parties_json' => 'array',
            'bench_json' => 'array',
            'counsel_json' => 'array',
            'filing_date' => 'date',
            'decision_date' => 'date',
            'is_reported' => 'boolean',
            'laws_applied_json' => 'array',
            'legal_issues_json' => 'array',
            'party_one_evidence_json' => 'array',
            'party_two_evidence_json' => 'array',
            'court_reasoning_json' => 'array',
            'cited_cases_json' => 'array',
            'keywords_json' => 'array',
            'evidence_json' => 'array',
            'ai_confidence' => 'float',
            'ai_extracted_at' => 'datetime',
            'is_demo' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function scopeForJurisdictions(Builder $query, array $jurisdictionIds): Builder
    {
        return $query->whereIn('jurisdiction_id', $jurisdictionIds);
    }

    public function scopeHideDemo(Builder $query): Builder
    {
        return $query->where('is_demo', false);
    }

    public function isAiDone(): bool
    {
        return $this->ai_status === 'done';
    }

    public function needsReview(): bool
    {
        return str_contains((string) $this->ai_notes, 'needs_review');
    }

    /** @return array{party_one: string, party_two: string} */
    public function partyLabels(): array
    {
        return CaseLabels::for($this->case_type, $this->party_one_role, $this->party_two_role);
    }

    public function dispositionEnum(): ?CaseDisposition
    {
        return CaseDisposition::tryNormalize($this->disposition);
    }

    public function dispositionLabel(): ?string
    {
        return $this->dispositionEnum()?->label();
    }

    public function winnerEnum(): CaseWinner
    {
        return CaseWinner::tryNormalize($this->winner) ?? CaseWinner::NotStated;
    }

    public function displayWinnerLabel(): string
    {
        if (filled($this->winner_label)) {
            return (string) $this->winner_label;
        }

        $labels = $this->partyLabels();

        return match ($this->winnerEnum()) {
            CaseWinner::PartyOne => $labels['party_one'],
            CaseWinner::PartyTwo => $labels['party_two'],
            CaseWinner::Split => 'Split',
            CaseWinner::None => 'None',
            CaseWinner::NotStated => 'Not stated in judgment',
        };
    }

    public function primarySourceUrl(): ?string
    {
        foreach ([$this->judgment_url, $this->pdf_url] as $url) {
            if (filled($url)) {
                return $url;
            }
        }

        return null;
    }
}

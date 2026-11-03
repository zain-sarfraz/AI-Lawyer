<?php

namespace App\Domain\CaseLaw;

use App\Jobs\ExtractCaseInsightsJob;
use App\Models\CaseLawRecord;
use App\Models\Court;
use App\Models\Jurisdiction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CaseLawUpserter
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(Jurisdiction $jurisdiction, array $payload): CaseLawRecord
    {
        $source = (string) ($payload['source'] ?? 'unknown');
        $externalId = (string) ($payload['external_id'] ?? '');
        if ($externalId === '') {
            throw new \InvalidArgumentException('external_id is required');
        }

        $courtId = $payload['court_id'] ?? null;
        if (! $courtId && ! empty($payload['external_court_id'])) {
            $courtId = Court::query()
                ->where('jurisdiction_id', $jurisdiction->id)
                ->where('external_court_id', $payload['external_court_id'])
                ->value('id');
        }
        if (! $courtId && ! empty($payload['court_name'])) {
            $courtId = Court::query()
                ->where('jurisdiction_id', $jurisdiction->id)
                ->where('name', $payload['court_name'])
                ->value('id');
        }

        $judgmentUrl = $payload['judgment_url'] ?? null;
        $pdfUrl = $payload['pdf_url'] ?? null;
        // Never fall back to a site homepage — only store direct links when provided.
        $sourceUrl = $judgmentUrl ?: ($pdfUrl ?: null);

        $attributes = [
            'jurisdiction_id' => $jurisdiction->id,
            'court_id' => $courtId,
            'title' => (string) ($payload['title'] ?? 'Untitled'),
            'case_number' => $payload['case_number'] ?? null,
            'citation' => $payload['citation'] ?? null,
            'neutral_citation' => $payload['neutral_citation'] ?? null,
            'case_type' => $payload['case_type'] ?? null,
            'decision_date' => isset($payload['decision_date'])
                ? Carbon::parse($payload['decision_date'])->toDateString()
                : null,
            'filing_date' => isset($payload['filing_date'])
                ? Carbon::parse($payload['filing_date'])->toDateString()
                : null,
            'author_judge' => $payload['author_judge'] ?? null,
            'bench_json' => $payload['bench_json'] ?? null,
            'counsel_json' => $payload['counsel_json'] ?? null,
            'judgment_url' => $judgmentUrl,
            'pdf_url' => $pdfUrl,
            'source_url' => $sourceUrl,
            'full_text' => $payload['full_text'] ?? null,
            'ai_summary' => $payload['ai_summary'] ?? null,
            'disposition' => $payload['disposition'] ?? null,
            'laws_applied_json' => $payload['laws_applied_json'] ?? null,
            'legal_issues_json' => $payload['legal_issues_json'] ?? null,
            'keywords_json' => $payload['keywords_json'] ?? null,
            'party_one_name' => $payload['party_one_name'] ?? null,
            'party_two_name' => $payload['party_two_name'] ?? null,
            'is_demo' => (bool) ($payload['is_demo'] ?? false),
            'fetched_at' => now(),
        ];

        // Avoid wiping richer AI fields on re-fetch when the adapter omits them.
        $attributes = array_filter(
            $attributes,
            static fn ($value, $key) => ! in_array($key, [
                'ai_summary', 'disposition', 'laws_applied_json', 'legal_issues_json',
                'keywords_json', 'full_text', 'author_judge', 'bench_json', 'counsel_json',
                'party_one_name', 'party_two_name', 'case_type',
            ], true) || $value !== null,
            ARRAY_FILTER_USE_BOTH
        );

        $record = CaseLawRecord::query()->updateOrCreate(
            [
                'source' => $source,
                'external_id' => $externalId,
            ],
            $attributes
        );

        $this->maybeDispatchAi($record);

        return $record->fresh() ?? $record;
    }

    private function maybeDispatchAi(CaseLawRecord $record): void
    {
        if (blank($record->full_text)) {
            return;
        }
        if (in_array($record->ai_status, ['done', 'processing'], true)) {
            return;
        }

        try {
            ExtractCaseInsightsJob::dispatch($record->id)->onQueue('case-law-ai');
            $record->update(['ai_status' => 'queued']);
        } catch (\Throwable $e) {
            Log::warning('case_law.ai_dispatch_failed', [
                'id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

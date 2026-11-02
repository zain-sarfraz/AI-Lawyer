<?php

namespace App\Jobs;

use App\Domain\CaseLaw\AiExtractor;
use App\Models\CaseLawRecord;
use App\Support\Legal\CaseLabels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtractCaseInsightsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $caseLawRecordId) {}

    public function handle(AiExtractor $extractor): void
    {
        $record = CaseLawRecord::query()->find($this->caseLawRecordId);
        if (! $record || blank($record->full_text)) {
            return;
        }

        $record->update(['ai_status' => 'processing']);

        try {
            $result = $extractor->extract((string) $record->full_text);
            $labels = CaseLabels::for($result['case_type']);

            $winnerLabel = match ($result['winner']) {
                'party_one' => $labels['party_one'],
                'party_two' => $labels['party_two'],
                'split' => 'Split',
                'none' => 'None',
                default => 'Not stated in judgment',
            };

            $parties = $result['parties'];
            $partyOne = $parties[0] ?? ['name' => '', 'role' => $labels['party_one']];
            $partyTwo = $parties[1] ?? ['name' => '', 'role' => $labels['party_two']];

            $notes = null;
            if ($result['confidence'] < 0.6) {
                $notes = 'needs_review: confidence below 0.6';
            }

            $record->update([
                'case_type' => $result['case_type'],
                'party_one_name' => $partyOne['name'] ?: $record->party_one_name,
                'party_one_role' => $partyOne['role'] ?: $labels['party_one'],
                'party_two_name' => $partyTwo['name'] ?: $record->party_two_name,
                'party_two_role' => $partyTwo['role'] ?: $labels['party_two'],
                'parties_json' => $parties,
                'bench_json' => $result['bench'],
                'author_judge' => $result['author_judge'] ?: null,
                'counsel_json' => $result['counsel'] !== [] ? $result['counsel'] : $record->counsel_json,
                'ai_summary' => $result['summary'],
                'legal_issues_json' => $result['legal_issues'],
                'laws_applied_json' => $result['laws_applied'],
                'party_one_evidence_json' => $result['party_one_evidence'],
                'party_two_evidence_json' => $result['party_two_evidence'],
                'court_reasoning_json' => $result['court_reasoning'],
                'winner' => $result['winner'],
                'winner_label' => $winnerLabel,
                'disposition' => $result['disposition'],
                'final_order' => $result['final_order'] ?: null,
                'cited_cases_json' => $result['cited_cases'],
                'keywords_json' => $result['keywords'],
                'ai_confidence' => $result['confidence'],
                'ai_model' => (string) config('case_law.anthropic.model'),
                'ai_extracted_at' => now(),
                'ai_status' => 'done',
                'ai_notes' => $notes,
                // Legacy field kept for older UI fragments.
                'evidence_json' => [
                    'plaintiff' => array_column($result['party_one_evidence'], 'point'),
                    'defendant' => array_column($result['party_two_evidence'], 'point'),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('case_law.ai.failed', [
                'id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            $record->update(['ai_status' => 'failed', 'ai_notes' => $e->getMessage()]);
            throw $e;
        }
    }
}

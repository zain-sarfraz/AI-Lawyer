<?php

namespace App\Domain\CaseLaw;

use App\Enums\CaseDisposition;
use App\Enums\CaseWinner;
use InvalidArgumentException;

final class CaseExtractionValidator
{
    private const CASE_TYPES = ['criminal', 'civil', 'writ', 'appeal', 'family', 'tax', 'other'];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(array $payload): array
    {
        $required = [
            'case_type', 'parties', 'bench', 'author_judge', 'summary', 'legal_issues',
            'laws_applied', 'party_one_evidence', 'party_two_evidence', 'court_reasoning',
            'winner', 'disposition', 'final_order', 'cited_cases', 'keywords', 'confidence',
        ];
        // counsel is optional for older extractions; default to [].

        foreach ($required as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidArgumentException("Missing extraction field: {$key}");
            }
        }

        $caseType = strtolower((string) $payload['case_type']);
        if (! in_array($caseType, self::CASE_TYPES, true)) {
            throw new InvalidArgumentException("Invalid case_type: {$caseType}");
        }

        $winner = CaseWinner::tryNormalize((string) $payload['winner']);
        if ($winner === null) {
            throw new InvalidArgumentException('Invalid winner value.');
        }

        $disposition = CaseDisposition::tryNormalize((string) $payload['disposition']);
        if ($disposition === null) {
            throw new InvalidArgumentException('Invalid disposition value.');
        }

        return [
            'case_type' => $caseType,
            'parties' => $this->parties((array) $payload['parties']),
            'bench' => array_values(array_filter(array_map('strval', (array) $payload['bench']))),
            'author_judge' => (string) ($payload['author_judge'] ?? ''),
            'counsel' => $this->counsel((array) ($payload['counsel'] ?? [])),
            'summary' => (string) ($payload['summary'] ?? ''),
            'legal_issues' => array_values(array_filter(array_map('strval', (array) $payload['legal_issues']))),
            'laws_applied' => $this->laws((array) $payload['laws_applied']),
            'party_one_evidence' => $this->points((array) $payload['party_one_evidence']),
            'party_two_evidence' => $this->points((array) $payload['party_two_evidence']),
            'court_reasoning' => $this->points((array) $payload['court_reasoning']),
            'winner' => $winner->value,
            'disposition' => $disposition->value,
            'final_order' => (string) ($payload['final_order'] ?? ''),
            'cited_cases' => $this->cited((array) $payload['cited_cases']),
            'keywords' => array_values(array_filter(array_map('strval', (array) $payload['keywords']))),
            'confidence' => max(0.0, min(1.0, (float) $payload['confidence'])),
        ];
    }

    /** @param  list<mixed>  $rows */
    private function parties(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'name' => (string) ($row['name'] ?? ''),
                'role' => (string) ($row['role'] ?? ''),
            ];
        }

        return $out;
    }

    /** @param  list<mixed>  $rows */
    private function counsel(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'side' => (string) ($row['side'] ?? ''),
                'instructed_by' => ($row['instructed_by'] ?? null) ? (string) $row['instructed_by'] : null,
                'role' => (string) ($row['role'] ?? 'counsel'),
            ];
        }

        return $out;
    }

    /** @param  list<mixed>  $rows */
    private function laws(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'statute' => (string) ($row['statute'] ?? ''),
                'section' => (string) ($row['section'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ];
        }

        return $out;
    }

    /** @param  list<mixed>  $rows */
    private function points(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_string($row)) {
                $out[] = ['point' => $row, 'paragraph_ref' => null];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'point' => (string) ($row['point'] ?? ''),
                'paragraph_ref' => $row['paragraph_ref'] ?? null,
            ];
        }

        return $out;
    }

    /** @param  list<mixed>  $rows */
    private function cited(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'title' => (string) ($row['title'] ?? ''),
                'citation' => (string) ($row['citation'] ?? ''),
                'context' => (string) ($row['context'] ?? ''),
            ];
        }

        return $out;
    }
}

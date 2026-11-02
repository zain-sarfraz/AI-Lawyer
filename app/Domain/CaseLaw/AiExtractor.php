<?php

namespace App\Domain\CaseLaw;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class AiExtractor
{
    public function __construct(private readonly CaseExtractionValidator $validator) {}

    /**
     * @return array<string, mixed>
     */
    public function extract(string $fullText): array
    {
        $apiKey = (string) config('case_law.anthropic.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $chunkSize = (int) config('case_law.anthropic.max_chunk_chars', 12000);
        $chunks = $this->chunk($fullText, $chunkSize);

        $partials = [];
        foreach ($chunks as $i => $chunk) {
            $partials[] = $this->callModel($apiKey, $chunk, chunkIndex: $i + 1, total: count($chunks));
        }

        $merged = $this->mergePartials($partials);

        try {
            return $this->validator->validate($merged);
        } catch (InvalidArgumentException $e) {
            // Retry once with a merge-pass prompt on the combined JSON.
            $retry = $this->callModel(
                $apiKey,
                "Merge and correct this extraction JSON into the required schema. Previous error: {$e->getMessage()}\n\n".json_encode($merged),
                chunkIndex: 1,
                total: 1,
                systemExtra: 'Return only corrected JSON matching the schema. Use not_stated / empty arrays when unknown.'
            );

            return $this->validator->validate($retry);
        }
    }

    /**
     * Validate a payload without calling the API (for tests / re-save).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validateOnly(array $payload): array
    {
        return $this->validator->validate($payload);
    }

    /** @return array<string, mixed> */
    private function callModel(string $apiKey, string $content, int $chunkIndex, int $total, string $systemExtra = ''): array
    {
        $system = <<<'SYS'
You extract structured litigation insights from court judgments.
Return ONLY valid JSON with this schema:
{
  "case_type": "criminal|civil|writ|appeal|family|tax|other",
  "parties": [{"name": "", "role": ""}],
  "bench": [""],
  "author_judge": "",
  "counsel": [{"name": "", "side": "Appellant|Respondent|Claimant|Defendant|Petitioner|other", "instructed_by": "", "role": "counsel|solicitor|advocate"}],
  "summary": "3-5 sentences, plain language",
  "legal_issues": [""],
  "laws_applied": [{"statute": "", "section": "", "description": ""}],
  "party_one_evidence": [{"point": "", "paragraph_ref": ""}],
  "party_two_evidence": [{"point": "", "paragraph_ref": ""}],
  "court_reasoning": [{"point": "", "paragraph_ref": ""}],
  "winner": "party_one|party_two|split|none|not_stated",
  "disposition": "allowed|dismissed|partly_allowed|reversed|upheld|remanded|acquitted|convicted|withdrawn|not_stated",
  "final_order": "",
  "cited_cases": [{"title": "", "citation": "", "context": ""}],
  "keywords": [""],
  "confidence": 0.0
}
Rules:
- Use ONLY information explicitly present in the judgment. If not stated, use "not_stated" or []. Never invent evidence, citations, sections, or paragraph numbers.
- Keep evidence (witness testimony, documents, exhibits) separate from court_reasoning (the court's analysis).
- winner is which party prevailed; disposition is what the court did to the claim/appeal. They are different.
- Include paragraph_ref where the judgment has numbered paragraphs; otherwise null.
SYS;

        if ($systemExtra !== '') {
            $system .= "\n".$systemExtra;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('case_law.anthropic.model'),
            'max_tokens' => 4000,
            'system' => $system,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Judgment chunk {$chunkIndex}/{$total}:\n\n".$content,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic API error: '.$response->status().' '.$response->body());
        }

        return $this->decodeJson((string) data_get($response->json(), 'content.0.text', ''));
    }

    /**
     * @param  list<array<string, mixed>>  $partials
     * @return array<string, mixed>
     */
    private function mergePartials(array $partials): array
    {
        if (count($partials) === 1) {
            return $partials[0];
        }

        $merged = $partials[0];
        foreach (array_slice($partials, 1) as $part) {
            foreach (['legal_issues', 'keywords', 'bench'] as $listKey) {
                $merged[$listKey] = array_values(array_unique(array_merge(
                    array_map('strval', (array) ($merged[$listKey] ?? [])),
                    array_map('strval', (array) ($part[$listKey] ?? []))
                )));
            }
            foreach (['laws_applied', 'party_one_evidence', 'party_two_evidence', 'court_reasoning', 'cited_cases', 'parties'] as $objKey) {
                $merged[$objKey] = array_merge((array) ($merged[$objKey] ?? []), (array) ($part[$objKey] ?? []));
            }
            if (($part['confidence'] ?? 0) > ($merged['confidence'] ?? 0)) {
                $merged['confidence'] = $part['confidence'];
            }
            foreach (['summary', 'final_order', 'author_judge', 'case_type', 'winner', 'disposition'] as $scalar) {
                if (blank($merged[$scalar] ?? null) && filled($part[$scalar] ?? null)) {
                    $merged[$scalar] = $part[$scalar];
                }
            }
        }

        return $merged;
    }

    /** @return list<string> */
    private function chunk(string $text, int $size): array
    {
        $text = trim($text);
        if ($text === '') {
            return [''];
        }
        $chunks = [];
        $len = mb_strlen($text);
        for ($i = 0; $i < $len; $i += $size) {
            $chunks[] = mb_substr($text, $i, $size);
        }

        return $chunks;
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $text): array
    {
        $text = trim($text);
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $text = $m[0];
        }
        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI response was not valid JSON.');
        }

        return $decoded;
    }
}

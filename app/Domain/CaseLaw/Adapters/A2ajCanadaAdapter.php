<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Free A2AJ Canadian Legal Data API (no API key).
 *
 * @see https://api.a2aj.ca/docs
 * @see https://a2aj.ca/data/
 */
class A2ajCanadaAdapter implements CaseSourceAdapter
{
    /** @var array<string, list<string>> */
    private const DATASETS_BY_JURISDICTION = [
        'CA-FED' => ['SCC', 'FCA', 'FC'],
        'CA-ON' => ['ONCA'],
    ];

    /** @var array<string, string> */
    private const COURT_BY_DATASET = [
        'SCC' => 'Supreme Court of Canada',
        'FCA' => 'Federal Court of Appeal',
        'FC' => 'Federal Court',
        'ONCA' => 'Court of Appeal for Ontario',
    ];

    public function search(array $params): iterable
    {
        $base = $this->baseUrl();
        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $code = strtoupper((string) ($params['jurisdiction_code'] ?? 'CA-ON'));
        $datasets = $this->datasetsFor($code);
        $query = trim((string) ($params['query'] ?? config('case_law.canada_cases.default_query', 'v')));
        if ($query === '') {
            $query = 'v';
        }

        $perDataset = max(1, (int) ceil($limit / max(1, count($datasets))));
        $fetched = 0;
        $delayMs = (int) config('case_law.fetch.delay_ms', 250);

        foreach ($datasets as $dataset) {
            if ($fetched >= $limit) {
                break;
            }

            $need = min($perDataset, $limit - $fetched);
            $size = min(50, $need);
            $payload = $this->getJson($base.'/search', [
                'query' => $query,
                'doc_type' => 'cases',
                'search_type' => 'name',
                'dataset' => $dataset,
                'size' => $size,
                'search_language' => (string) config('case_law.canada_cases.language', 'en'),
                'sort_results' => 'newest_first',
            ]);

            $results = $payload['results'] ?? [];
            if (! is_array($results) || $results === []) {
                continue;
            }

            foreach ($results as $row) {
                if ($fetched >= $limit) {
                    break 2;
                }
                if (! is_array($row)) {
                    continue;
                }

                $normalized = $this->normalize($row, $dataset);
                if (($normalized['title'] ?? '') === '' || ($normalized['judgment_url'] ?? null) === null) {
                    continue;
                }

                yield $normalized;
                $fetched++;

                if ($delayMs > 0) {
                    usleep(min($delayMs, 500) * 1000);
                }
            }
        }
    }

    public function fetch(string $externalId): array
    {
        if (! preg_match('/^a2aj-(.+)$/i', $externalId, $m)) {
            throw new RuntimeException("Invalid A2AJ external id: {$externalId}");
        }

        $citation = rawurldecode($m[1]);
        $payload = $this->getJson($this->baseUrl().'/fetch', [
            'citation' => $citation,
            'doc_type' => 'cases',
            'output_language' => (string) config('case_law.canada_cases.language', 'en'),
        ]);

        $row = $payload['results'][0] ?? null;
        if (! is_array($row)) {
            throw new RuntimeException("A2AJ case not found for citation: {$citation}");
        }

        $dataset = strtoupper((string) ($row['dataset'] ?? 'SCC'));

        return $this->normalize($row, $dataset);
    }

    /** @return list<string> */
    private function datasetsFor(string $code): array
    {
        $map = (array) config('case_law.canada_cases.datasets_by_jurisdiction', []);
        $configured = $map[$code] ?? null;
        if (is_array($configured) && $configured !== []) {
            return array_values(array_map('strval', $configured));
        }

        return self::DATASETS_BY_JURISDICTION[$code] ?? ['SCC'];
    }

    private function baseUrl(): string
    {
        $base = rtrim((string) config('case_law.canada_cases.base_url', ''), '/');
        if ($base === '') {
            throw new RuntimeException('Canada cases API URL missing. Set CANADA_CASES_API_URL in .env');
        }

        return $base;
    }

    /** @param  array<string, mixed>  $query */
    private function getJson(string $url, array $query = []): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('case_law.fetch.user_agent'),
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->retry(
                    (int) config('case_law.fetch.retry_times', 3),
                    (int) config('case_law.fetch.retry_sleep_ms', 1500)
                )
                ->get($url, $query);

            if (! $response->successful()) {
                throw new RuntimeException('A2AJ HTTP '.$response->status().' for '.$url);
            }

            $json = $response->json();
            if (! is_array($json)) {
                throw new RuntimeException('A2AJ returned non-JSON for '.$url);
            }

            return $json;
        } catch (Throwable $e) {
            Log::error('a2aj.request_failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row, string $dataset): array
    {
        $lang = (string) config('case_law.canada_cases.language', 'en');
        $title = trim((string) ($row['name_'.$lang] ?? $row['name_en'] ?? $row['name'] ?? ''));
        $citation = trim((string) ($row['citation_'.$lang] ?? $row['citation_en'] ?? $row['citation'] ?? ''));
        $url = trim((string) ($row['url_'.$lang] ?? $row['url_en'] ?? $row['url'] ?? ''));
        if ($url !== '' && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        $decisionDate = null;
        $rawDate = $row['document_date_'.$lang] ?? $row['document_date_en'] ?? $row['document_date'] ?? null;
        if (is_string($rawDate) && $rawDate !== '') {
            try {
                $decisionDate = Carbon::parse($rawDate)->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        }

        $dataset = strtoupper($dataset !== '' ? $dataset : (string) ($row['dataset'] ?? ''));
        $externalKey = $citation !== '' ? $citation : ($dataset.'-'.md5($title.$url));
        $parties = $this->parseParties($title);
        $courtName = self::COURT_BY_DATASET[$dataset]
            ?? (string) ($row['description_en'] ?? $dataset);

        return [
            'external_id' => 'a2aj-'.$externalKey,
            'source' => 'a2aj',
            'title' => $title !== '' ? $title : 'Untitled',
            'case_number' => null,
            'citation' => $citation !== '' ? $citation : null,
            'neutral_citation' => $citation !== '' ? $citation : null,
            'case_type' => $this->inferCaseType($title, $dataset),
            'decision_date' => $decisionDate,
            'author_judge' => null,
            'bench_json' => null,
            'judgment_url' => $url !== '' ? $url : null,
            'pdf_url' => null,
            'court_name' => $courtName,
            'ai_summary' => null,
            'keywords_json' => null,
            'party_one_name' => $parties[0],
            'party_two_name' => $parties[1],
            'full_text' => null,
            'is_demo' => false,
            'disposition' => null,
        ];
    }

    private function inferCaseType(string $title, string $dataset): string
    {
        $blob = strtolower($title.' '.$dataset);

        return match (true) {
            str_contains($blob, 'criminal') || str_contains($blob, 'r. v') || str_contains($blob, 'r v ') => 'criminal',
            str_contains($blob, 'family') || str_contains($blob, 'divorce') || str_contains($blob, 'custody') => 'family',
            str_contains($blob, 'tax') || $dataset === 'TCC' => 'tax',
            str_contains($blob, 'appeal') || str_ends_with($dataset, 'CA') => 'appeal',
            default => 'civil',
        };
    }

    /** @return array{0: ?string, 1: ?string} */
    private function parseParties(string $title): array
    {
        if (preg_match('/^(.+?)\s+v(?:s\.?)?\s+(.+)$/iu', $title, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [null, null];
    }
}

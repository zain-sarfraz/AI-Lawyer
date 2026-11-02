<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * CanLII REST API (metadata only) — requires CANLII_API_KEY.
 *
 * Flow:
 * 1) caseBrowse/{lang}/{databaseId}/ → list recent cases
 * 2) caseBrowse/{lang}/{databaseId}/{caseId}/ → enrich url, date, keywords
 *
 * @see https://www.canlii.org/en/info/api.html
 */
class CanliiAdapter implements CaseSourceAdapter
{
    public function search(array $params): iterable
    {
        $apiKey = (string) config('case_law.canlii.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('CanLII API key missing. Set CANLII_API_KEY in .env');
        }

        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $lang = (string) config('case_law.canlii.language', 'en');
        $databases = $this->databasesForJurisdiction((string) ($params['jurisdiction_code'] ?? 'CA-FED'));
        $enrich = (bool) ($params['enrich'] ?? true);
        $perDb = max(1, (int) ceil($limit / max(1, count($databases))));
        $fetched = 0;
        $delayMs = (int) config('case_law.fetch.delay_ms', 200);

        foreach ($databases as $databaseId) {
            if ($fetched >= $limit) {
                break;
            }

            $need = min($perDb, $limit - $fetched);
            $offset = 0;

            while ($fetched < $limit && $need > 0) {
                $batch = min(100, $need);
                $list = $this->getJson($lang.'/'.$databaseId.'/', [
                    'offset' => $offset,
                    'resultCount' => $batch,
                ]);

                $cases = $list['cases'] ?? [];
                if ($cases === []) {
                    break;
                }

                foreach ($cases as $case) {
                    if ($fetched >= $limit) {
                        break 3;
                    }

                    $caseId = $this->caseId($case);
                    if ($caseId === '') {
                        continue;
                    }

                    $meta = $enrich
                        ? $this->safeCaseMeta($lang, $databaseId, $caseId)
                        : null;

                    yield $this->normalize($case, $meta, $databaseId);

                    $fetched++;
                    $need--;
                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                }

                $offset += count($cases);
                if (count($cases) < $batch) {
                    break;
                }
            }
        }
    }

    public function fetch(string $externalId): array
    {
        $apiKey = (string) config('case_law.canlii.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('CanLII API key missing. Set CANLII_API_KEY in .env');
        }

        // external_id format: canlii-{databaseId}-{caseId}
        if (! preg_match('/^canlii-([a-z0-9-]+)-(.+)$/i', $externalId, $m)) {
            throw new RuntimeException("Invalid CanLII external id: {$externalId}");
        }

        $databaseId = $m[1];
        $caseId = $m[2];
        $lang = (string) config('case_law.canlii.language', 'en');
        $meta = $this->getJson($lang.'/'.$databaseId.'/'.$caseId.'/');

        return $this->normalize([
            'databaseId' => $databaseId,
            'caseId' => ['en' => $caseId],
            'title' => $meta['title'] ?? '',
            'citation' => $meta['citation'] ?? null,
        ], $meta, $databaseId);
    }

    /** @return list<string> */
    private function databasesForJurisdiction(string $code): array
    {
        $map = (array) config('case_law.canlii.databases_by_jurisdiction', []);
        $dbs = $map[strtoupper($code)] ?? null;
        if (is_array($dbs) && $dbs !== []) {
            return array_values(array_map('strval', $dbs));
        }

        return [(string) config('case_law.canlii.default_database', 'csc-scc')];
    }

    /** @param  array<string, mixed>  $query */
    private function getJson(string $path, array $query = []): array
    {
        $base = rtrim((string) config('case_law.canlii.base_url', 'https://api.canlii.org/v1/caseBrowse'), '/');
        $url = $base.'/'.ltrim($path, '/');

        $response = Http::withHeaders([
            'User-Agent' => (string) config('case_law.fetch.user_agent'),
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->retry(
                (int) config('case_law.fetch.retry_times', 3),
                (int) config('case_law.fetch.retry_sleep_ms', 1500)
            )
            ->get($url, array_merge($query, [
                'api_key' => (string) config('case_law.canlii.api_key'),
            ]));

        if (! $response->successful()) {
            throw new RuntimeException('CanLII HTTP '.$response->status().' for '.$path);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('CanLII returned non-JSON for '.$path);
        }

        if (isset($json['error'])) {
            throw new RuntimeException('CanLII error: '.json_encode($json));
        }

        return $json;
    }

    /** @return array<string, mixed>|null */
    private function safeCaseMeta(string $lang, string $databaseId, string $caseId): ?array
    {
        try {
            return $this->getJson($lang.'/'.$databaseId.'/'.$caseId.'/');
        } catch (Throwable $e) {
            Log::warning('canlii.meta_failed', [
                'databaseId' => $databaseId,
                'caseId' => $caseId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** @param  array<string, mixed>  $case */
    private function caseId(array $case): string
    {
        $raw = $case['caseId'] ?? null;
        if (is_string($raw)) {
            return $raw;
        }
        if (is_array($raw)) {
            return (string) ($raw['en'] ?? $raw['fr'] ?? reset($raw) ?: '');
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>
     */
    private function normalize(array $case, ?array $meta, string $databaseId): array
    {
        $caseId = $this->caseId($case) ?: (string) ($meta['caseId'] ?? '');
        $title = trim((string) ($meta['title'] ?? $case['title'] ?? 'Untitled'));
        $citation = $meta['citation'] ?? $case['citation'] ?? null;
        $url = $meta['url'] ?? null;
        if (is_string($url) && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        $decisionDate = null;
        if (! empty($meta['decisionDate'])) {
            try {
                $decisionDate = Carbon::parse((string) $meta['decisionDate'])->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        }

        $keywords = [];
        if (! empty($meta['keywords']) && is_string($meta['keywords'])) {
            $keywords = array_values(array_filter(array_map(
                static fn ($k) => trim($k),
                preg_split('/\s*[—\-]\s*|\s*,\s*/u', $meta['keywords']) ?: []
            )));
        }

        $parties = $this->parseParties($title);

        return [
            'external_id' => 'canlii-'.$databaseId.'-'.$caseId,
            'source' => 'canlii',
            'title' => $title,
            'case_number' => $meta['docketNumber'] ?? null,
            'citation' => $citation,
            'neutral_citation' => $citation,
            'case_type' => $this->inferCaseType($title, $databaseId, $keywords),
            'decision_date' => $decisionDate,
            'author_judge' => null,
            'bench_json' => null,
            'judgment_url' => $url,
            'pdf_url' => null,
            'court_name' => $this->mapCourtName($databaseId, (string) ($meta['databaseId'] ?? $databaseId)),
            'ai_summary' => null,
            'keywords_json' => $keywords !== [] ? $keywords : null,
            'party_one_name' => $parties[0],
            'party_two_name' => $parties[1],
            'full_text' => null,
            'is_demo' => false,
            'disposition' => null,
        ];
    }

    private function mapCourtName(string $databaseId, string $fallback): string
    {
        return match (strtolower($databaseId)) {
            'csc-scc' => 'Supreme Court of Canada',
            'fca' => 'Federal Court of Appeal',
            'fc' => 'Federal Court',
            'onca' => 'Court of Appeal for Ontario',
            'onsc' => 'Ontario Superior Court of Justice',
            'bcca' => 'Court of Appeal for British Columbia',
            'abca' => 'Court of Appeal of Alberta',
            'qcca' => 'Quebec Court of Appeal',
            default => $fallback !== '' ? $fallback : 'Canadian Court',
        };
    }

    /** @param  list<string>  $keywords */
    private function inferCaseType(string $title, string $databaseId, array $keywords): string
    {
        $blob = strtolower($title.' '.$databaseId.' '.implode(' ', $keywords));

        return match (true) {
            str_contains($blob, 'criminal') || str_contains($blob, 'r. v') || str_contains($blob, 'r v ') => 'criminal',
            str_contains($blob, 'family') || str_contains($blob, 'divorce') || str_contains($blob, 'custody') => 'family',
            str_contains($blob, 'tax') || str_contains($blob, 'tcc') => 'tax',
            str_contains($blob, 'charter') || str_contains($blob, 'judicial review') || str_contains($blob, 'administrative') => 'writ',
            str_contains($blob, 'appeal') || str_ends_with($databaseId, 'ca') => 'appeal',
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

<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use App\Domain\CaseLaw\CourtListenerRateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * CourtListener REST v4 opinions ingest for US jurisdictions.
 *
 * Default search path maps listing fields from /search/ only (fast — ~20 cases per request).
 * Pass enrich=true to also pull cluster + opinion full text for AI.
 */
class CourtListenerAdapter implements CaseSourceAdapter
{
    public function __construct(private readonly CourtListenerRateLimiter $limiter) {}

    public function search(array $params): iterable
    {
        $this->assertEnabled();

        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $court = (string) ($params['court'] ?? config('case_law.courtlistener.default_court', 'scotus'));
        $from = (string) ($params['from'] ?? now()->subYears(3)->toDateString());
        $to = (string) ($params['to'] ?? now()->toDateString());
        $jurisdiction = strtoupper((string) ($params['jurisdiction_code'] ?? 'US-FED'));
        $enrich = (bool) ($params['enrich'] ?? false);
        $cursorKey = 'courtlistener:cursor:'.$jurisdiction.':'.$court.':'.$from.':'.$to;
        $next = $params['cursor'] ?? Cache::get($cursorKey);

        $fetched = 0;
        $pageSize = min(100, max(20, $limit));
        $pageUrl = is_string($next) && $next !== ''
            ? $next
            : $this->baseUrl().'/search/?'.http_build_query([
                'type' => 'o',
                'court' => $court,
                'filed_after' => $from,
                'filed_before' => $to,
                'order_by' => 'dateFiled desc',
                'page_size' => $pageSize,
            ]);

        while ($fetched < $limit && $pageUrl) {
            $json = $this->getJson($pageUrl);
            $results = $json['results'] ?? [];
            if ($results === []) {
                Cache::forget($cursorKey);
                break;
            }

            foreach ($results as $hit) {
                if ($fetched >= $limit) {
                    break 2;
                }

                $clusterId = (string) ($hit['cluster_id'] ?? data_get($hit, 'cluster.id') ?? '');
                if ($clusterId === '' && ! empty($hit['cluster'])) {
                    $clusterId = (string) (is_numeric($hit['cluster']) ? $hit['cluster'] : basename((string) $hit['cluster']));
                }
                if ($clusterId === '') {
                    continue;
                }

                try {
                    yield $enrich
                        ? $this->normalizeCluster($clusterId, $hit, $court)
                        : $this->normalizeSearchHit($clusterId, $hit, $court);
                    $fetched++;
                } catch (Throwable $e) {
                    Log::warning('courtlistener.normalize_failed', [
                        'cluster' => $clusterId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $pageUrl = $json['next'] ?? null;
            if (is_string($pageUrl) && $pageUrl !== '') {
                Cache::put($cursorKey, $pageUrl, now()->addDays(7));
            } else {
                Cache::forget($cursorKey);
            }
        }
    }

    public function fetch(string $externalId): array
    {
        $this->assertEnabled();
        $id = preg_replace('/\D+/', '', $externalId) ?: $externalId;

        return $this->normalizeCluster((string) $id, [], null);
    }

    /**
     * Fast path: map list fields from search hit only (no cluster/opinion HTTP).
     *
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>
     */
    private function normalizeSearchHit(string $clusterId, array $hit, ?string $fallbackCourt): array
    {
        $title = (string) ($hit['caseName'] ?? $hit['case_name'] ?? 'Untitled');
        $citations = [];
        if (! empty($hit['citation'])) {
            $citations = is_array($hit['citation'])
                ? array_values(array_filter(array_map('strval', $hit['citation'])))
                : [(string) $hit['citation']];
        }

        $decisionDate = $hit['dateFiled'] ?? $hit['date_filed'] ?? null;
        if ($decisionDate) {
            try {
                $decisionDate = Carbon::parse((string) $decisionDate)->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        }

        $absolute = (string) ($hit['absolute_url'] ?? $hit['absoluteUrl'] ?? '');
        if ($absolute === '' && $clusterId !== '') {
            // Public cluster URL is enough for realOnly() and the Cases UI.
            $absolute = 'https://www.courtlistener.com/opinion/'.$clusterId.'/';
        }
        if ($absolute !== '' && str_starts_with($absolute, '/')) {
            $absolute = 'https://www.courtlistener.com'.$absolute;
        }

        $courtId = (string) ($fallbackCourt ?? $hit['court_id'] ?? $hit['court'] ?? 'scotus');

        return [
            'external_id' => 'cl-'.$clusterId,
            'source' => 'courtlistener',
            'title' => $title,
            'citation' => $citations[0] ?? null,
            'neutral_citation' => $citations[0] ?? null,
            'other_citations' => count($citations) > 1 ? array_slice($citations, 1) : null,
            'case_type' => $this->inferCaseType($title),
            'decision_date' => $decisionDate,
            'author_judge' => null,
            'bench_json' => null,
            'judgment_url' => $absolute !== '' ? $absolute : null,
            'pdf_url' => null,
            'external_court_id' => $courtId,
            'court_name' => $this->courtNameFor($courtId),
            'full_text' => null,
            'is_demo' => false,
            'disposition' => null,
            'ai_summary' => null,
        ];
    }

    /** @param  array<string, mixed>  $hit */
    private function normalizeCluster(string $clusterId, array $hit, ?string $fallbackCourt): array
    {
        $cluster = $this->getJson($this->baseUrl().'/clusters/'.$clusterId.'/');
        $opinionId = (string) (data_get($cluster, 'sub_opinions.0')
            ?? data_get($hit, 'id')
            ?? '');
        if (str_contains($opinionId, '/')) {
            $opinionId = basename(rtrim($opinionId, '/'));
        }

        $opinion = $opinionId !== ''
            ? $this->getJson($this->baseUrl().'/opinions/'.$opinionId.'/')
            : [];

        $base = $this->normalizeSearchHit($clusterId, array_merge($hit, [
            'caseName' => $cluster['case_name'] ?? ($hit['caseName'] ?? null),
            'dateFiled' => $cluster['date_filed'] ?? ($hit['dateFiled'] ?? null),
            'absolute_url' => $cluster['absolute_url'] ?? ($hit['absolute_url'] ?? null),
            'court_id' => $fallbackCourt
                ?? data_get($cluster, 'docket.court_id')
                ?? $cluster['court_id']
                ?? ($hit['court_id'] ?? null),
        ]), $fallbackCourt);

        $citations = [];
        foreach ((array) ($cluster['citations'] ?? []) as $cite) {
            if (is_array($cite) && ! empty($cite['cite'])) {
                $citations[] = (string) $cite['cite'];
            } elseif (is_string($cite)) {
                $citations[] = $cite;
            }
        }
        if ($citations !== []) {
            $base['citation'] = $citations[0];
            $base['neutral_citation'] = $citations[0];
            $base['other_citations'] = count($citations) > 1 ? array_slice($citations, 1) : null;
        }

        $text = (string) (
            $opinion['plain_text']
            ?? $opinion['html_with_citations']
            ?? $opinion['html']
            ?? $opinion['xml_harvard']
            ?? ''
        );
        if (str_contains($text, '<')) {
            $text = trim(html_entity_decode(strip_tags($text)));
        }

        $judges = array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) ($cluster['judges'] ?? '')) ?: [])));
        $base['author_judge'] = $judges[0] ?? null;
        $base['bench_json'] = $judges !== [] ? $judges : null;
        $base['pdf_url'] = $opinion['download_url'] ?? null;
        $base['full_text'] = $text !== '' ? $text : null;

        return $base;
    }

    private function courtNameFor(string $courtId): string
    {
        return match ($courtId) {
            'scotus' => 'Supreme Court of the United States',
            'cadc' => 'U.S. Court of Appeals for the D.C. Circuit',
            'cafc' => 'U.S. Court of Appeals for the Federal Circuit',
            default => str_starts_with($courtId, 'ca')
                ? 'U.S. Court of Appeals ('.$courtId.')'
                : $courtId,
        };
    }

    private function inferCaseType(string $title): string
    {
        $t = strtolower($title);

        return match (true) {
            str_contains($t, 'united states v') || str_contains($t, 'u.s. v') || str_contains($t, 'state v') => 'criminal',
            str_contains($t, 'ex parte') || str_contains($t, 'habeas') => 'writ',
            default => 'civil',
        };
    }

    /** @return array<string, mixed> */
    private function getJson(string $url): array
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            $this->limiter->assertCanRequest();

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Token '.$this->token(),
                    'Accept' => 'application/json',
                    'User-Agent' => (string) config('case_law.fetch.user_agent'),
                ])
                    ->timeout((int) config('case_law.courtlistener.timeout', 60))
                    ->connectTimeout(20)
                    ->get($url);
            } catch (Throwable $e) {
                if ($attempt >= 5) {
                    throw new RuntimeException('CourtListener request failed: '.$e->getMessage(), 0, $e);
                }
                Log::warning('courtlistener.request_retry', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                sleep(min(30, 2 ** $attempt));
                continue;
            }

            if ($response->status() === 429) {
                $retry = (int) ($response->header('Retry-After') ?: 0);
                $this->limiter->honorRetryAfter($retry ?: null);
                if ($attempt >= 5) {
                    throw new RuntimeException('CourtListener rate limited repeatedly (429).');
                }
                continue;
            }

            if (! $response->successful()) {
                if ($attempt < 5 && in_array($response->status(), [502, 503, 504], true)) {
                    sleep(min(30, 2 ** $attempt));
                    continue;
                }
                throw new RuntimeException('CourtListener HTTP '.$response->status().' for '.$url);
            }

            $this->limiter->recordSuccess();
            $json = $response->json();
            if (! is_array($json)) {
                throw new RuntimeException('CourtListener returned non-JSON for '.$url);
            }

            return $json;
        }
    }

    private function assertEnabled(): void
    {
        if (! config('case_law.courtlistener.enabled')) {
            throw new RuntimeException('CourtListener is disabled. Set COURTLISTENER_ENABLED=true.');
        }
        if ($this->token() === '') {
            throw new RuntimeException('COURTLISTENER_API_TOKEN is missing.');
        }
    }

    private function token(): string
    {
        return (string) config('case_law.courtlistener.api_token', '');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('case_law.courtlistener.base_url', 'https://www.courtlistener.com/api/rest/v4'), '/');
    }
}

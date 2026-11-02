<?php

namespace App\Domain\Legal;

use App\Domain\Legal\Contracts\LegalDataProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CourtListener (Free Law Project) — United States case-law provider.
 *
 * Endpoints (REST v4):
 *   GET /search/                 case-law search (type=o → opinion clusters)
 *   GET /search/?count=on        count-only probe for category coverage
 *   GET /clusters/{id}/          single opinion-cluster detail
 *   GET /courts/                 court directory (jurisdiction, location)
 *
 * Documented constraints that shape this implementation:
 *   • Every REST request counts against the user quota — default 5/min,
 *     50/hour, 125/day. Requests are therefore cached and paced.
 *   • Anonymous search requests return HTTP 401; a token is mandatory.
 *   • `page` pagination is capped at 100 pages.
 *   • Long OR-chains are discouraged; category queries stay short.
 *   • Opinions carry no party-relative outcome field, so win/loss cannot be
 *     derived from this provider. Nothing is ever invented to fill the gap.
 *
 * @see https://www.courtlistener.com/help/api/
 */
class CourtListenerService implements LegalDataProvider
{
    /** Minimum spacing between two live API calls (5 requests/minute). */
    private const MIN_REQUEST_SPACING_SECONDS = 13;

    /** How long a throttled state blocks further live calls. */
    private const THROTTLE_FALLBACK_SECONDS = 20;

    /** Hard cap enforced by CourtListener for `page` based pagination. */
    private const MAX_PAGES = 100;

    /*
     * Verified against the live API: `/search/` and `/courts/` ignore `page_size`
     * (and the `fields` / `omit` / `count` helpers), so every page always carries
     * exactly 20 records. Pagination maths must therefore use this fixed size and
     * never the size a caller asked for.
     */
    private const PROVIDER_PAGE_SIZE = 20;

    private string $baseUrl;

    private ?string $token;

    private int $cacheTtl;

    private int $staleTtl;

    /**
     * Category definitions mapped to CourtListener search queries.
     *
     * `terms` are deliberately short keyword groups (CourtListener asks
     * integrators to avoid long OR-chains); `types` are the case types offered
     * in the searchable case-type selector.
     */
    private const CATEGORIES = [
        'civil' => [
            'label'       => 'Civil',
            'description' => 'Contracts, torts, property and general civil litigation',
            'icon'        => 'scales',
            'terms'       => '"civil action" OR negligence OR contractual',
            'types'       => ['Contract', 'Personal Injury', 'Property', 'Tort'],
        ],
        'criminal' => [
            'label'       => 'Criminal',
            'description' => 'Prosecution, sentencing, habeas and criminal appeals',
            'icon'        => 'shield',
            'terms'       => 'criminal OR sentencing OR indictment',
            'types'       => ['Drug Offenses', 'Firearms', 'Fraud', 'Habeas Corpus', 'Violent Crime'],
        ],
        'family' => [
            'label'       => 'Family',
            'description' => 'Custody, support and domestic-relations matters',
            'icon'        => 'users',
            'terms'       => 'custody OR "child support" OR "domestic relations"',
            'types'       => ['Custody', 'Child Support', 'Divorce', 'Adoption', 'Domestic Violence'],
        ],
        'employment' => [
            'label'       => 'Employment',
            'description' => 'Discrimination, wage-hour and wrongful-dismissal disputes',
            'icon'        => 'briefcase',
            'terms'       => 'employment OR "wrongful termination" OR "Title VII" OR FLSA',
            'types'       => ['Employment Discrimination', 'Wage and Hour', 'Wrongful Termination', 'Labor Relations', 'Retaliation'],
        ],
        'constitutional' => [
            'label'       => 'Constitutional',
            'description' => 'Fundamental rights, due process and equal protection',
            'icon'        => 'book',
            'terms'       => '"due process" OR "equal protection" OR "first amendment"',
            'types'       => ['First Amendment', 'Due Process', 'Equal Protection', 'Search and Seizure', 'Voting Rights'],
        ],
        'immigration' => [
            'label'       => 'Immigration',
            'description' => 'Removal, asylum and naturalization proceedings',
            'icon'        => 'arrow-up-right',
            'terms'       => 'immigration OR asylum OR deportation',
            'types'       => ['Asylum', 'Removal Defense', 'Naturalization', 'Visa', 'Detention'],
        ],
        'bankruptcy' => [
            'label'       => 'Bankruptcy',
            'description' => 'Chapter 7, 11 and 13 proceedings and creditor disputes',
            'icon'        => 'chart',
            'terms'       => 'bankruptcy OR "chapter 11" OR "chapter 13"',
            'types'       => ['Chapter 7', 'Chapter 11', 'Chapter 13', 'Creditor Dispute', 'Discharge'],
        ],
        'intellectual_property' => [
            'label'       => 'Intellectual Property',
            'description' => 'Patent, trademark, copyright and trade-secret litigation',
            'icon'        => 'clipboard',
            'terms'       => 'patent OR trademark OR copyright',
            'types'       => ['Patent', 'Trademark', 'Copyright', 'Trade Secret'],
        ],
        'tax' => [
            'label'       => 'Tax',
            'description' => 'Tax liability, refunds and Tax Court proceedings',
            'icon'        => 'journal',
            'terms'       => '"tax court" OR "internal revenue" OR deficiency',
            'types'       => ['Tax Liability', 'Refund', 'Penalty', 'Employment Tax', 'Collection'],
        ],
    ];

    /**
     * Court jurisdiction codes published by the `/courts/` directory, read off the
     * live response body (codes are multi-character, not single letters). Codes that
     * are not listed here are displayed verbatim instead of being given a label that
     * has not been confirmed against the source.
     */
    private const JURISDICTION_LABELS = [
        'F'   => 'Federal appellate',
        'FBP' => 'Federal bankruptcy appellate',
        'FS'  => 'Federal specialty tribunal',
        'MA'  => 'Military appellate',
    ];

    public function __construct()
    {
        $this->baseUrl  = rtrim((string) config('services.courtlistener.base_url'), '/');
        $this->token    = $this->readToken();
        $this->cacheTtl = max(60, (int) config('services.courtlistener.cache_ttl', 3600));
        $this->staleTtl = max($this->cacheTtl, (int) config('services.courtlistener.stale_ttl', 86400));
    }

    /* -----------------------------------------------------------------
     |  Provider metadata
     | ----------------------------------------------------------------- */

    public function providerName(): string
    {
        return 'CourtListener';
    }

    public function supportedCountries(): array
    {
        return ['US'];
    }

    public function isConfigured(): bool
    {
        return filled($this->token) && str_starts_with($this->baseUrl, 'https://');
    }

    public function categories(): array
    {
        return collect(self::CATEGORIES)
            ->map(fn (array $cat, string $key) => [
                'key'         => $key,
                'label'       => $cat['label'],
                'description' => $cat['description'],
                'icon'        => $cat['icon'],
            ])
            ->all();
    }

    /**
     * Query terms for one category, used by the artisan connection check so the
     * probe exercises the same matching the screen uses.
     */
    public function probeTerms(string $category): string
    {
        return self::CATEGORIES[$category]['terms'] ?? $category;
    }

    public function caseTypes(?string $category): array
    {
        $types = self::CATEGORIES[$category]['types'] ?? [];

        if ($types === []) {
            $types = collect(self::CATEGORIES)->flatMap(fn (array $c) => $c['types'])->unique()->values()->all();
        }

        sort($types, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_map(fn (string $type) => [
            'value' => $type,
            'label' => $type,
        ], $types));
    }

    /* -----------------------------------------------------------------
     |  Search
     | ----------------------------------------------------------------- */

    /** {@inheritDoc} */
    public function search(array $filters): array
    {
        $filters = $this->normaliseFilters($filters);

        if (! $this->isConfigured()) {
            return $this->result(
                reason: 'unconfigured',
                message: 'CourtListener is not connected yet. Add COURTLISTENER_API_TOKEN to your environment.',
                degraded: true,
            );
        }

        if ($unsupported = $this->unsupportedCountryMessage($filters['country'])) {
            return $this->result(reason: 'unsupported_country', message: $unsupported, degraded: true, country: $filters['country']);
        }

        $key = 'cl_search_'.md5(json_encode($filters));

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        if ($wait = $this->throttleWaitSeconds()) {
            // Serve the last known response (marked stale) rather than burning quota.
            $stale = Cache::get($key.'_stale');
            if (is_array($stale)) {
                return array_merge($stale, [
                    'stale'   => true,
                    'message' => 'Showing cached records — CourtListener rate limit reached, live data available in '.$wait.'s.',
                ]);
            }

            return $this->result(
                reason: 'rate_limited',
                message: "CourtListener allows {$this->requestsPerMinute()} requests per minute. Retry in {$wait}s.",
                degraded: true,
                retryAfter: $wait,
            );
        }

        try {
            $response = $this->httpClient()->get("{$this->baseUrl}/search/", $this->queryParams($filters));
        } catch (ConnectionException $e) {
            Log::warning('CourtListener connection error', ['error' => $e->getMessage()]);

            return $this->connectionFailure($key, $e);
        } catch (Throwable $e) {
            Log::error('CourtListener search exception', ['error' => $e->getMessage()]);

            return $this->result(reason: 'error', message: 'The legal-data request failed unexpectedly.', degraded: true);
        }

        $this->markRequestSent();

        if ($response->status() === 429) {
            $wait = $this->registerThrottle($response);

            return $this->result(
                reason: 'rate_limited',
                message: "CourtListener rate limit reached. Retry in {$wait}s.",
                degraded: true,
                retryAfter: $wait,
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            Log::warning('CourtListener rejected credentials', ['status' => $response->status()]);

            return $this->result(
                reason: 'unconfigured',
                message: 'CourtListener rejected the API token. Verify COURTLISTENER_API_TOKEN is valid and active.',
                degraded: true,
            );
        }

        if ($response->failed()) {
            Log::warning('CourtListener search failed', [
                'status' => $response->status(),
                'body'   => mb_substr((string) $response->body(), 0, 400),
            ]);

            return $this->result(
                reason: 'error',
                message: 'CourtListener is temporarily unavailable (HTTP '.$response->status().').',
                degraded: true,
            );
        }

        $payload = $response->json();
        if (! is_array($payload) || ! array_key_exists('results', $payload)) {
            Log::warning('CourtListener returned an unexpected payload', ['keys' => array_keys((array) $payload)]);

            return $this->result(reason: 'error', message: 'CourtListener returned data in an unexpected format.', degraded: true);
        }

        $data = $this->transformSearchResults($payload, $filters);

        Cache::put($key, $data, $this->cacheTtl);
        Cache::put($key.'_stale', $data, $this->staleTtl);

        return $data;
    }

    /* -----------------------------------------------------------------
     |  Single record
     | ----------------------------------------------------------------- */

    /** {@inheritDoc} */
    public function getCase(string $id): ?array
    {
        if (! $this->isConfigured() || ! preg_match('/^\d{1,12}$/', $id)) {
            return null;
        }

        $key = "cl_cluster_{$id}";

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached['data'] ?? null;
        }

        if ($wait = $this->throttleWaitSeconds()) {
            return ['unavailable' => true, 'retry_after' => $wait];
        }

        try {
            $response = $this->httpClient()->get("{$this->baseUrl}/clusters/{$id}/");
            $this->markRequestSent();

            if ($response->status() === 429) {
                return ['unavailable' => true, 'retry_after' => $this->registerThrottle($response)];
            }

            if ($response->failed()) {
                return null;
            }

            $cluster = $response->json();
            if (! is_array($cluster)) {
                return null;
            }

            $data = $this->transformCluster($cluster);
            Cache::put($key, ['data' => $data], $this->staleTtl);

            return $data;
        } catch (Throwable $e) {
            Log::warning('CourtListener cluster fetch failed', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /* -----------------------------------------------------------------
     |  Category coverage (count-only probe)
     | ----------------------------------------------------------------- */

    /** {@inheritDoc} */
    public function countCategory(string $category, ?string $dateFrom = null, ?string $dateTo = null): ?int
    {
        if (! $this->isConfigured() || ! isset(self::CATEGORIES[$category])) {
            return null;
        }

        $key = "cl_count_{$category}_".($dateFrom ?? '').'_'.($dateTo ?? '');

        if (Cache::has($key)) {
            $cached = Cache::get($key);

            return is_int($cached) ? $cached : null;
        }

        if ($this->throttleWaitSeconds() > 0) {
            return null; // leave the card unverified instead of burning quota
        }

        $filters = $this->normaliseFilters([
            'category'  => $category,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);

        try {
            // `count` is returned for every search request, so a plain query is enough.
            $response = $this->httpClient()->get("{$this->baseUrl}/search/", $this->queryParams($filters));
            $this->markRequestSent();

            if ($response->status() === 429) {
                $this->registerThrottle($response);

                return null;
            }

            if ($response->failed()) {
                return null;
            }

            $count = (int) ($response->json('count') ?? 0);
            Cache::put($key, $count, 86400);

            return $count;
        } catch (Throwable $e) {
            Log::warning('CourtListener coverage probe failed', ['category' => $category, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /* -----------------------------------------------------------------
     |  Court directory
     | ----------------------------------------------------------------- */

    /** {@inheritDoc} */
    public function courts(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $cached = Cache::get('cl_courts_directory');
        if (is_array($cached)) {
            return $cached;
        }

        if ($this->throttleWaitSeconds() > 0) {
            return [];
        }

        $courts = [];

        try {
            for ($page = 1; $page <= 3; $page++) {
                // `page_size` is ignored by this endpoint: each page holds 20 courts.
                $response = $this->httpClient()->get("{$this->baseUrl}/courts/", [
                    'page'      => $page,
                    'order_by'  => 'full_name',
                ]);
                $this->markRequestSent();

                if ($response->failed()) {
                    break;
                }

                $payload = $response->json();
                foreach ((array) ($payload['results'] ?? []) as $court) {
                    $id = (string) ($court['id'] ?? '');
                    if ($id === '') {
                        continue;
                    }

                    $courts[] = [
                        'id'           => $id,
                        'name'         => (string) ($court['full_name'] ?? $court['short_name'] ?? $id),
                        'citation'     => (string) ($court['citation_string'] ?? ''),
                        'jurisdiction' => $this->jurisdictionLabel($court['jurisdiction'] ?? null),
                        'jurisdiction_code' => strtoupper(trim((string) ($court['jurisdiction'] ?? ''))),
                        // `/courts/` publishes no city or state, so this stays empty
                        // unless the source starts returning those fields.
                        'location'     => $this->courtLocation($court),
                        'in_use'       => (bool) ($court['in_use'] ?? true),
                    ];
                }

                // Stay inside the per-minute quota even mid-directory: stop paging
                // rather than sending a request the account is not allowed to make.
                if (empty($payload['next']) || $this->throttleWaitSeconds() > 0) {
                    break;
                }
            }
        } catch (Throwable $e) {
            Log::warning('CourtListener courts fetch failed', ['error' => $e->getMessage()]);

            return $courts; // whatever was gathered already
        }

        if ($courts !== []) {
            Cache::put('cl_courts_directory', array_values($courts), 86400);
        }

        return array_values($courts);
    }

    /* -----------------------------------------------------------------
     |  Query building
     | ----------------------------------------------------------------- */

    private function normaliseFilters(array $filters): array
    {
        $category = is_string($filters['category'] ?? null) && isset(self::CATEGORIES[$filters['category']])
            ? $filters['category']
            : '';

        $caseType = $this->sanitisePhrase((string) ($filters['case_type'] ?? ''));
        $query    = mb_substr(trim((string) ($filters['q'] ?? '')), 0, 180);

        return [
            'category'  => $category,
            'case_type' => $caseType,
            'q'         => $query,
            'court'     => $this->sanitiseCourtIds((string) ($filters['court'] ?? '')),
            'country'   => mb_strtoupper(mb_substr(trim((string) ($filters['country'] ?? '')), 0, 2)),
            'date_from' => $this->sanitiseDate($filters['date_from'] ?? null),
            'date_to'   => $this->sanitiseDate($filters['date_to'] ?? null),
            'page'      => min(self::MAX_PAGES, max(1, (int) ($filters['page'] ?? 1))),
            // The provider ignores a requested page size: a page is always 20 records.
            'per_page'  => self::PROVIDER_PAGE_SIZE,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function queryParams(array $filters): array
    {
        $q = $this->buildQuery($filters['category'], $filters['case_type'], $filters['q']);

        $params = [
            'type'     => 'o',        // opinion clusters = decided case law
            'order_by' => 'dateFiled desc', // dateFiled is the date the decision was issued
            'page'     => $filters['page'],
        ];

        if ($q !== '') {
            $params['q'] = $q;
        }

        if ($filters['date_from'] !== '') {
            $params['filed_after'] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '') {
            $params['filed_before'] = $filters['date_to'];
        }

        if ($filters['court'] !== '') {
            // CourtListener accepts one court parameter per id.
            $params['court'] = explode(',', $filters['court']);
        }

        return $params;
    }

    private function buildQuery(string $category, string $caseType, string $userQuery): string
    {
        $parts = [];

        if ($category !== '' && isset(self::CATEGORIES[$category])) {
            $parts[] = '('.self::CATEGORIES[$category]['terms'].')';
        }

        if ($caseType !== '') {
            // Match the structured nature-of-suit value, or the phrase anywhere in the text.
            $parts[] = '(suitNature:"'.$caseType.'" OR "'.$caseType.'")';
        }

        if ($userQuery !== '') {
            $parts[] = '('.$userQuery.')';
        }

        return implode(' AND ', $parts);
    }

    private function sanitisePhrase(string $value): string
    {
        $value = preg_replace('/[^\p{L}\p{N}\s&\'\/.-]/u', '', trim($value)) ?? '';

        return mb_substr(trim($value), 0, 60);
    }

    private function sanitiseCourtIds(string $value): string
    {
        $ids = collect(explode(',', strtolower($value)))
            ->map(fn (string $id) => preg_replace('/[^a-z0-9_-]/', '', trim($id)) ?? '')
            ->filter()
            ->take(5)
            ->values()
            ->all();

        return implode(',', $ids);
    }

    private function sanitiseDate(mixed $value): string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function unsupportedCountryMessage(string $country): ?string
    {
        if ($country === '' || in_array($country, $this->supportedCountries(), true)) {
            return null;
        }

        return "CourtListener only covers United States court records — there is no verified data source yet for \"{$country}\". "
            .'No results are shown rather than guessing with records from another country.';
    }

    /* -----------------------------------------------------------------
     |  Response transformation
     | ----------------------------------------------------------------- */

    private function transformSearchResults(array $payload, array $filters): array
    {
        $total = (int) ($payload['count'] ?? 0);
        $raw   = $payload['results'] ?? [];

        $seen    = [];
        $records = [];
        foreach ((array) $raw as $item) {
            $record = $this->transformSearchItem($item, $filters['category']);
            $token  = $record['case_id'] !== '' ? $record['case_id'] : $record['case_name'].'|'.$record['date_filed'];

            if (isset($seen[$token])) {
                continue; // provider duplicates guard
            }

            $seen[$token] = true;
            $records[] = $record;
        }

        // The provider always returns 20 records per page whatever was requested.
        $perTotal = self::PROVIDER_PAGE_SIZE;
        $pages    = $total > 0 ? min(self::MAX_PAGES, (int) ceil($total / $perTotal)) : 0;

        return [
            'success'      => true,
            'reason'       => $records === [] && $total === 0 ? 'empty' : 'ok',
            'message'      => $records === [] && $total === 0
                ? 'CourtListener has no published opinions matching these filters.'
                : null,
            'results'      => $records,
            'total'        => $total,
            'page'         => $filters['page'],
            'per_page'     => $perTotal,
            'page_size'    => $perTotal,
            'total_pages'  => $pages,
            'has_next'     => (bool) ($payload['next'] ?? false) && $filters['page'] < self::MAX_PAGES,
            'has_prev'     => (bool) ($payload['previous'] ?? false) && $filters['page'] > 1,
            'stats'        => $this->computeStats($records, $total),
            'provider'     => $this->providerName(),
            'country'      => $filters['country'] === '' ? 'US' : $filters['country'],
            'stale'        => false,
            'retry_after'  => null,
            'fetched_at'   => now()->toIso8601String(),
            'range'        => [
                'date_from' => $filters['date_from'],
                'date_to'   => $filters['date_to'],
            ],
        ];
    }

    private function transformSearchItem(array $item, string $category): array
    {
        // Search results use camelCase for corpus fields (documented).
        $caseName   = $this->pick($item, ['caseName', 'case_name', 'caseNameShort', 'case_name_short'], 'Untitled case');
        $courtId    = (string) $this->pick($item, ['court_id', 'court_id_str'], '');
        $courtName  = (string) $this->pick($item, ['court', 'court_name', 'court_citation_string'], '');
        $clusterId  = (string) $this->pick($item, ['cluster_id', 'id'], '');
        $absolute   = (string) $this->pick($item, ['absolute_url', 'absoluteUrl'], '');
        $dateFiled  = $this->pick($item, ['dateFiled', 'date_filed'], null);
        $snippet    = $this->extractSnippet($item);
        $suitNature = (string) $this->pick($item, ['suitNature', 'nature_of_suit'], '');
        $posture    = (string) $this->pick($item, ['posture'], '');
        $cause      = (string) $this->pick($item, ['cause'], '');
        $status     = (string) $this->pick($item, ['status', 'precedential_status', 'precedentialStatus'], '');
        // `court_jurisdiction` is published for many (not all) search rows; it is a
        // provider code, so it is exposed separately from any human-readable label.
        $jurisdictionCode = strtoupper(trim((string) $this->pick($item, ['court_jurisdiction', 'courtJurisdiction'], '')));

        return [
            'case_name'      => strip_tags($caseName),
            'case_id'        => $clusterId,
            'docket_number'  => (string) $this->pick($item, ['docketNumber', 'docket_number'], ''),
            'citation'       => $this->formatCitations($item),
            'court_id'       => $courtId,
            'court_name'     => strip_tags($courtName),
            'court_short'    => (string) $this->pick($item, ['court_citation_string'], ''),
            'country'        => 'US',
            'jurisdiction'   => $this->jurisdictionFromCourtId($courtId) ?? $this->jurisdictionLabel($jurisdictionCode),
            'jurisdiction_code' => $jurisdictionCode !== '' ? $jurisdictionCode : null,
            'category'       => self::CATEGORIES[$category]['label'] ?? '',
            'nature_of_suit' => $suitNature,
            'posture'        => $posture,
            'cause'          => $cause,
            'date_filed'     => $dateFiled,
            'status'         => $status,
            'judgment_text'  => $this->extractJudgmentText($item),
            'outcome_class'  => $this->classifyOutcome($item),
            'outcome_basis'  => 'provider_publishes_no_party_relative_outcome',
            'snippet'        => $snippet,
            'url'            => $this->buildUrl($absolute, $clusterId),
            'provider'       => $this->providerName(),
        ];
    }

    private function transformCluster(array $data): array
    {
        $absolute = (string) $this->pick($data, ['absolute_url'], '');
        $id       = (string) $this->pick($data, ['id'], '');

        // On the cluster endpoint `court` is a hyperlink to the court resource;
        // its last path segment is the court id. A plain string is a court name.
        $courtRaw   = $data['court'] ?? null;
        $courtName  = is_string($courtRaw) && ! str_starts_with($courtRaw, 'http') ? $courtRaw : '';
        $courtId    = (string) $this->pick($data, ['court_id'], '');

        if ($courtId === '' && is_string($courtRaw) && str_starts_with($courtRaw, 'http')) {
            $courtId = basename(trim($courtRaw, '/'));
        }

        return [
            'case_name'      => strip_tags((string) $this->pick($data, ['case_name_full', 'case_name'], 'Untitled case')),
            'case_id'        => $id,
            'citation'       => $this->formatCitations($data),
            'docket_number'  => (string) $this->pick($data, ['docket_number'], ''),
            'country'        => 'US',
            'court_id'       => $courtId,
            'court_name'     => strip_tags($courtName),
            'jurisdiction'   => $this->jurisdictionFromCourtId($courtId),
            'date_filed'     => $this->pick($data, ['date_filed'], null),
            'judges'         => (string) $this->pick($data, ['judges'], ''),
            'nature_of_suit' => (string) $this->pick($data, ['nature_of_suit'], ''),
            'posture'        => (string) $this->pick($data, ['posture'], ''),
            'status'         => (string) $this->pick($data, ['precedential_status'], ''),
            'syllabus'       => strip_tags((string) $this->pick($data, ['syllabus'], '')),
            'judgment_text'  => $this->extractJudgmentText($data),
            'outcome_class'  => $this->classifyOutcome($data),
            'outcome_basis'  => 'provider_publishes_no_party_relative_outcome',
            'url'            => $this->buildUrl($absolute, $id),
            'provider'       => $this->providerName(),
        ];
    }

    /** Snippets live on nested opinions for type=o results. */
    private function extractSnippet(array $item): string
    {
        $snippet = (string) $this->pick($item, ['snippet'], '');

        if ($snippet === '') {
            foreach ((array) ($item['opinions'] ?? []) as $opinion) {
                if (is_array($opinion) && filled($opinion['snippet'] ?? null)) {
                    $snippet = (string) $opinion['snippet'];
                    break;
                }
            }
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($snippet)) ?? '');
    }

    /**
     * Only an explicit disposition/outcome field published by the provider is
     * shown. Nothing is inferred from free text, so no fabricated judgments.
     */
    private function extractJudgmentText(array $item): string
    {
        $value = $this->pick($item, ['disposition', 'outcome'], '');

        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        return mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? ''), 0, 180);
    }

    /**
     * Won/Lost is assigned only when the provider publishes BOTH an explicit
     * disposition and the party perspective it should be read from. CourtListener
     * opinion records carry neither, so they stay Unknown - outcomes are never
     * inferred from keywords in free text.
     */
    private function classifyOutcome(array $item): string
    {
        $declared = mb_strtolower((string) $this->pick($item, ['outcome_class'], ''));
        $basis    = mb_strtolower((string) $this->pick($item, ['outcome_basis', 'outcome_source'], ''));

        // A future provider may hand back a verified, party-relative class.
        if (in_array($declared, ['won', 'lost', 'pending'], true) && $basis !== '') {
            return $declared;
        }

        $disposition = mb_strtolower($this->extractJudgmentText($item));
        $perspective = mb_strtolower((string) $this->pick($item, ['party_perspective', 'party_position'], ''));

        if ($disposition !== '' && $perspective !== '') {
            // Only unambiguous, party-labelled perspectives are trusted.
            if (preg_match('/\b(for|with|in favor of|prevailed)\b/', $perspective)) {
                return 'won';
            }

            if (preg_match('/\b(against|without|lost)\b/', $perspective)) {
                return 'lost';
            }
        }

        $status = mb_strtolower((string) $this->pick($item, ['case_status'], ''));
        if (str_contains($status, 'pending') || str_contains($status, 'open')) {
            return 'pending';
        }

        return 'unknown';
    }

    private function formatCitations(array $item): string
    {
        $raw = $item['citation'] ?? [];

        if (is_string($raw) && trim($raw) !== '') {
            return trim($raw);
        }

        // `/search/` returns citation strings; `/clusters/{id}/` returns `citations`
        // as a list of objects, so both shapes are handled without assuming either.
        $rows = is_array($raw) ? $raw : [];
        $rows = array_values(array_filter(
            $rows,
            fn ($c) => is_string($c) ? trim($c) !== '' : (is_array($c) && filled($c['cite'] ?? null)),
        ));

        $citations = collect($rows)
            ->map(fn ($c) => is_array($c) ? trim((string) $c['cite']) : trim($c))
            ->filter()
            ->take(2)
            ->implode(', ');

        if ($citations !== '') {
            return $citations;
        }

        $clusterRows = array_filter($item['citations'] ?? [], fn ($c) => is_array($c) && filled($c['cite'] ?? null));
        if ($clusterRows !== []) {
            return implode(', ', array_slice(array_map(fn (array $c) => trim((string) $c['cite']), $clusterRows), 0, 2));
        }

        foreach (['neutralCite', 'neutral_cite', 'lexisCite', 'lexis_cite', 'westlawCite'] as $key) {
            if (filled($item[$key] ?? null)) {
                return (string) $item[$key];
            }
        }

        return '';
    }

    private function buildUrl(string $absolute, string $clusterId): string
    {
        if ($absolute !== '') {
            if (str_starts_with($absolute, 'http')) {
                return $absolute;
            }

            return 'https://www.courtlistener.com/'.ltrim($absolute, '/');
        }

        return $clusterId !== '' ? 'https://www.courtlistener.com/opinion/'.$clusterId.'/' : '';
    }

    private function pick(array $item, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                return $item[$key];
            }
        }

        return $default;
    }

    /* -----------------------------------------------------------------
     |  Statistics
     | ----------------------------------------------------------------- */

    private function computeStats(array $records, int $total): array
    {
        $outcomes = ['won' => 0, 'lost' => 0, 'pending' => 0, 'unknown' => 0];
        $courts   = [];
        $types    = [];

        foreach ($records as $record) {
            $class = $record['outcome_class'] ?? 'unknown';
            $outcomes[$class] = ($outcomes[$class] ?? 0) + 1;

            if (($record['court_name'] ?? '') !== '') {
                $courts[$record['court_name']] = ($courts[$record['court_name']] ?? 0) + 1;
            }

            if (($record['nature_of_suit'] ?? '') !== '') {
                $types[$record['nature_of_suit']] = ($types[$record['nature_of_suit']] ?? 0) + 1;
            }
        }

        $analyzed     = count($records);
        $classifiable = $outcomes['won'] + $outcomes['lost'];

        return [
            'total_found'       => $total,
            'analyzed'          => $analyzed,
            'outcomes'          => $outcomes,
            'classifiable'      => $classifiable,
            'win_pct'           => $classifiable > 0 ? round(($outcomes['won'] / $classifiable) * 100, 1) : null,
            'loss_pct'          => $classifiable > 0 ? round(($outcomes['lost'] / $classifiable) * 100, 1) : null,
            'courts'            => $courts,
            'case_types'        => $types,
            'outcome_note'      => $classifiable === 0
                ? 'CourtListener does not publish party-relative outcomes, so win/loss cannot be verified for these records.'
                : null,
        ];
    }

    /* -----------------------------------------------------------------
     |  Court helpers
     | ----------------------------------------------------------------- */

    private function jurisdictionFromCourtId(string $courtId): ?string
    {
        if ($courtId === '') {
            return null;
        }

        // Only structural, unambiguous identifiers. Everything else is left blank.
        return match (true) {
            $courtId === 'scotus'                       => 'Supreme Court of the United States',
            (bool) preg_match('/^ca\d+$|^cadc$|^cafc$/', $courtId) => 'Federal appellate',
            (bool) preg_match('/^(sc|bap)\w+$/', $courtId)        => 'Specialty federal',
            default                                              => null,
        };
    }

    private function jurisdictionLabel(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $code = strtoupper(trim($value));

        // Unknown codes are shown exactly as published rather than being labelled.
        return self::JURISDICTION_LABELS[$code] ?? (strlen($code) <= 6 ? $code : null);
    }

    private function courtLocation(array $court): ?string
    {
        $parts = array_filter([
            $court['city'] ?? null,
            $court['state_str'] ?? $court['state'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', array_map(strval(...), $parts));
    }

    /* -----------------------------------------------------------------
     |  Rate-limit / pacing helpers
     | ----------------------------------------------------------------- */

    private function requestsPerMinute(): int
    {
        return (int) config('services.courtlistener.requests_per_minute', 5);
    }

    /** Seconds until the next live call is allowed; 0 means allowed now. */
    private function throttleWaitSeconds(): int
    {
        $now = time();

        $wait = 0;

        foreach (['cl_throttle_until', 'cl_next_allowed_at'] as $key) {
            $until = Cache::get($key);
            if (is_int($until)) {
                $wait = max($wait, $until - $now);
            }
        }

        return max(0, $wait);
    }

    private function markRequestSent(): void
    {
        $spacing = max(1, (int) round(60 / max(1, $this->requestsPerMinute())));
        Cache::put('cl_next_allowed_at', time() + $spacing, $spacing + 5);
    }

    private function registerThrottle(\Illuminate\Http\Client\Response $response): int
    {
        $retryAfter = (int) ($response->header('Retry-After') ?: self::THROTTLE_FALLBACK_SECONDS);
        $retryAfter = max(5, min(3600, $retryAfter));

        Cache::put('cl_throttle_until', time() + $retryAfter, $retryAfter + 5);

        return $retryAfter;
    }

    private function connectionFailure(string $key, Throwable $e): array
    {
        $stale = Cache::get($key.'_stale');
        if (is_array($stale)) {
            return array_merge($stale, [
                'stale'   => true,
                'message' => 'CourtListener is unreachable — showing the last cached records.',
            ]);
        }

        Log::warning('CourtListener unavailable', ['error' => $e->getMessage()]);

        return $this->result(
            reason: 'error',
            message: 'CourtListener could not be reached. The service may be temporarily unavailable.',
            degraded: true,
        );
    }

    /* -----------------------------------------------------------------
     |  HTTP client
     | ----------------------------------------------------------------- */

    private function httpClient(): PendingRequest
    {
        $request = Http::timeout((int) config('services.courtlistener.timeout', 20))
            ->acceptJson()
            // Never retry automatically: every attempt consumes the daily quota.
            ->retry(1, 0, when: fn () => false, throw: false);

        if (filled($this->token)) {
            $request = $request->withHeaders(['Authorization' => 'Token '.$this->token]);
        }

        return $request;
    }

    /** Token lives only in server-side configuration; never surfaced to views. */
    private function readToken(): ?string
    {
        $token = config('services.courtlistener.api_token');

        return is_string($token) ? trim($token) : null;
    }

    /* -----------------------------------------------------------------
     |  Empty / degraded envelope
     | ----------------------------------------------------------------- */

    private function result(
        string $reason,
        ?string $message,
        bool $degraded = false,
        ?int $retryAfter = null,
        string $country = 'US',
    ): array {
        return [
            'success'      => in_array($reason, ['ok', 'empty'], true),
            'degraded'     => $degraded,
            'reason'       => $reason,
            'message'      => $message,
            'results'      => [],
            'total'        => 0,
            'page'         => 1,
            'per_page'     => 20,
            'page_size'    => 20,
            'total_pages'  => 0,
            'has_next'     => false,
            'has_prev'     => false,
            'retry_after'  => $retryAfter,
            'stale'        => false,
            'stats'        => [
                'total_found'  => 0,
                'analyzed'     => 0,
                'outcomes'     => ['won' => 0, 'lost' => 0, 'pending' => 0, 'unknown' => 0],
                'classifiable' => 0,
                'win_pct'      => null,
                'loss_pct'     => null,
                'courts'       => [],
                'case_types'   => [],
                'outcome_note' => 'No verified records were analysed for this selection.',
            ],
            'provider'     => $this->providerName(),
            'country'      => $country === '' ? 'US' : $country,
            'fetched_at'   => now()->toIso8601String(),
            'range'        => ['date_from' => '', 'date_to' => ''],
        ];
    }
}

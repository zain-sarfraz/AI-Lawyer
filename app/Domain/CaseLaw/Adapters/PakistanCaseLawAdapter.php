<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use App\Domain\CaseLaw\PakistanCaseLawMcpClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Fetches reported Pakistani judgments via the free Pakistan Case Law MCP.
 *
 * @see https://pakistancaselaw.com/for-ai
 */
class PakistanCaseLawAdapter implements CaseSourceAdapter
{
    /** @var array<string, string> */
    private const COURT_BY_JURISDICTION = [
        'PK-FED' => 'Supreme Court',
        'PK-SD' => 'Sindh High Court',
        'PK-PB' => 'Lahore High Court',
        'PK-IS' => 'Islamabad High Court',
    ];

    public function __construct(private readonly PakistanCaseLawMcpClient $client) {}

    public function search(array $params): iterable
    {
        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $jurisdiction = strtoupper((string) ($params['jurisdiction_code'] ?? ''));
        $court = self::COURT_BY_JURISDICTION[$jurisdiction]
            ?? (string) ($params['court'] ?? 'Supreme Court');

        $offset = 0;
        $fetched = 0;
        $enrich = (bool) ($params['enrich'] ?? false);
        $delayMs = $enrich ? (int) config('case_law.fetch.delay_ms', 2500) : 0;

        while ($fetched < $limit) {
            $pageLimit = min(50, $limit - $fetched);
            $arguments = array_filter([
                'court' => $court,
                'sort' => (string) ($params['sort'] ?? 'newest'),
                'limit' => $pageLimit,
                'offset' => $offset,
                'query' => $params['query'] ?? null,
                'year_from' => $params['year_from'] ?? null,
                'year_to' => $params['year_to'] ?? null,
                'response_format' => 'json',
            ], static fn ($v) => $v !== null && $v !== '');

            $result = $this->client->callTool('caselaw_search', $arguments);

            $items = $result['items'] ?? [];
            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                if ($fetched >= $limit) {
                    break 2;
                }

                $detail = $enrich ? $this->safeGetCase((int) ($item['id'] ?? 0)) : null;
                yield $this->normalize($item, $detail, $court);

                $fetched++;
                if ($delayMs > 0) {
                    usleep(min($delayMs, 1000) * 1000);
                }
            }

            if (empty($result['has_more'])) {
                break;
            }
            $offset = (int) ($result['next_offset'] ?? ($offset + count($items)));
        }
    }

    public function fetch(string $externalId): array
    {
        $id = (int) preg_replace('/\D+/', '', $externalId);
        if ($id < 1) {
            throw new RuntimeException("Invalid Pakistan Case Law id: {$externalId}");
        }

        $detail = $this->client->callTool('caselaw_get_case', [
            'case_id' => $id,
            'section' => 'summary',
            'response_format' => 'json',
        ]);

        return $this->normalize([
            'id' => $id,
            'title' => $detail['title'] ?? '',
            'citation' => $detail['citation'] ?? null,
            'court' => $detail['court'] ?? null,
            'year' => $detail['year'] ?? null,
            'url' => $detail['url'] ?? null,
            'author' => $detail['author'] ?? null,
            'result' => $detail['result'] ?? null,
        ], $detail, (string) ($detail['court'] ?? 'Supreme Court'));
    }

    /** @return array<string, mixed>|null */
    private function safeGetCase(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        try {
            return $this->client->callTool('caselaw_get_case', [
                'case_id' => $id,
                'section' => 'summary',
                'response_format' => 'json',
            ]);
        } catch (Throwable $e) {
            Log::warning('pcl.get_case_failed', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>|null  $detail
     * @return array<string, mixed>
     */
    private function normalize(array $item, ?array $detail, string $fallbackCourt): array
    {
        $id = (string) ($item['id'] ?? $detail['id'] ?? '');
        $headnote = is_array($detail['headnote'] ?? null) ? $detail['headnote'] : [];
        $laws = [];
        foreach ((array) ($headnote['laws'] ?? []) as $law) {
            $laws[] = [
                'statute' => (string) $law,
                'section' => '',
                'description' => '',
            ];
        }

        $decisionDate = null;
        if (! empty($detail['date'])) {
            try {
                $decisionDate = Carbon::parse((string) $detail['date'])->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        } elseif (! empty($item['year'])) {
            $decisionDate = ((int) $item['year']).'-01-01';
        }

        $judgmentUrl = $detail['url'] ?? $item['url'] ?? null;
        $title = (string) ($detail['title'] ?? $item['title'] ?? 'Untitled');
        $caseType = $this->inferCaseType($title, (array) ($headnote['keywords'] ?? []));
        $summary = (string) ($headnote['summary'] ?? '');
        $disposition = $this->mapDisposition((string) ($detail['result'] ?? $item['result'] ?? ''));
        if ($disposition === null && $summary !== '') {
            $disposition = $this->mapDisposition($summary);
        }

        $parties = $this->parseParties($title);

        return [
            'external_id' => 'pcl-'.$id,
            'source' => 'pakistan-case-law',
            'title' => $title,
            'case_number' => $detail['case_no'] ?? null,
            'citation' => $detail['citation'] ?? $item['citation'] ?? null,
            'neutral_citation' => $detail['citation'] ?? $item['citation'] ?? null,
            'case_type' => $caseType,
            'decision_date' => $decisionDate,
            'author_judge' => $detail['author'] ?? $item['author'] ?? null,
            'bench_json' => array_values(array_filter(array_map('trim', explode(',', (string) ($detail['judges'] ?? ''))))),
            'judgment_url' => $judgmentUrl,
            'pdf_url' => null,
            'court_name' => $this->mapCourtName((string) ($detail['court'] ?? $item['court'] ?? $fallbackCourt)),
            'ai_summary' => $summary !== '' ? $summary : null,
            'laws_applied_json' => $laws !== [] ? $laws : null,
            'keywords_json' => array_values((array) ($headnote['keywords'] ?? [])) ?: null,
            'legal_issues_json' => array_values((array) ($headnote['questions'] ?? [])) ?: null,
            'party_one_name' => $parties[0],
            'party_two_name' => $parties[1],
            'full_text' => null,
            'is_demo' => false,
            'disposition' => $disposition,
        ];
    }

    /** @return array{0: ?string, 1: ?string} */
    private function parseParties(string $title): array
    {
        if (preg_match('/^(.+?)\s+v(?:s\.?|ersus)\s+(.+)$/iu', $title, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [null, null];
    }

    private function mapCourtName(string $court): string
    {
        return match (true) {
            str_contains($court, 'Supreme') => 'Supreme Court of Pakistan',
            str_contains($court, 'Sindh') => 'Sindh High Court',
            str_contains($court, 'Lahore') || $court === 'LHC' => 'Lahore High Court',
            str_contains($court, 'Islamabad') => 'Islamabad High Court',
            str_contains($court, 'Shariat') => 'Federal Shariat Court',
            default => $court,
        };
    }

    /** @param  list<string>  $keywords */
    private function inferCaseType(string $title, array $keywords): string
    {
        $blob = strtolower($title.' '.implode(' ', $keywords));

        return match (true) {
            str_contains($blob, 'bail') || str_contains($blob, 'criminal') || str_contains($blob, 'murder') || str_contains($blob, 'ppc') => 'criminal',
            str_contains($blob, 'constitution') || str_contains($blob, 'writ') || str_contains($blob, 'article 199') => 'writ',
            str_contains($blob, 'tax') || str_contains($blob, 'inland revenue') => 'tax',
            str_contains($blob, 'family') || str_contains($blob, 'khula') || str_contains($blob, 'dower') => 'family',
            str_contains($blob, 'appeal') => 'appeal',
            default => 'civil',
        };
    }

    private function mapDisposition(string $result): ?string
    {
        $r = strtolower(trim($result));
        if ($r === '') {
            return null;
        }

        return match (true) {
            str_contains($r, 'dismiss') => 'dismissed',
            str_contains($r, 'allow') || str_contains($r, 'grant') => 'allowed',
            str_contains($r, 'partly') => 'partly_allowed',
            str_contains($r, 'revers') => 'reversed',
            str_contains($r, 'uphold') || str_contains($r, 'affirm') => 'upheld',
            str_contains($r, 'remand') => 'remanded',
            str_contains($r, 'acquit') => 'acquitted',
            str_contains($r, 'convict') => 'convicted',
            str_contains($r, 'withdraw') => 'withdrawn',
            default => 'not_stated',
        };
    }
}

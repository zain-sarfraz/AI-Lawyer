<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * Federal Court of Australia judgments RSS feed.
 *
 * @see https://www.judgments.fedcourt.gov.au/rss/fca-judgments
 */
class AustraliaFedCourtRssAdapter implements CaseSourceAdapter
{
    public function search(array $params): iterable
    {
        $limit = (int) ($params['limit'] ?? 0);
        // 0 / negative = no cap (consume the full feed).
        $unlimited = $limit <= 0;
        $fetched = 0;

        $xml = $this->loadFeed();
        foreach ($xml->channel->item ?? [] as $item) {
            if (! $unlimited && $fetched >= $limit) {
                break;
            }

            $row = $this->normalizeItem($item);
            if (($row['title'] ?? '') === '' || ($row['judgment_url'] ?? null) === null) {
                continue;
            }

            yield $row;
            $fetched++;
        }
    }

    public function fetch(string $externalId): array
    {
        if (! preg_match('/^fca-(.+)$/i', $externalId, $m)) {
            throw new RuntimeException("Invalid Federal Court of Australia external id: {$externalId}");
        }

        $needle = rawurldecode($m[1]);
        foreach ($this->search(['limit' => 0]) as $row) {
            if (($row['external_id'] ?? '') === 'fca-'.$needle
                || ($row['citation'] ?? '') === $needle
                || str_ends_with((string) ($row['judgment_url'] ?? ''), $needle)) {
                return $row;
            }
        }

        throw new RuntimeException("Federal Court of Australia judgment not found: {$externalId}");
    }

    private function loadFeed(): SimpleXMLElement
    {
        $url = rtrim((string) config('case_law.australia_cases.rss_url', ''), '/');
        if ($url === '') {
            throw new RuntimeException('Australia cases RSS URL missing. Set AUSTRALIA_CASES_API_URL in .env');
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('case_law.fetch.user_agent'),
                'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
            ])
                ->timeout(120)
                ->retry(
                    (int) config('case_law.fetch.retry_times', 3),
                    (int) config('case_law.fetch.retry_sleep_ms', 1500)
                )
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('Federal Court RSS HTTP '.$response->status());
            }

            return new SimpleXMLElement($response->body());
        } catch (Throwable $e) {
            Log::error('australia_fca.rss_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /** @return array<string, mixed> */
    private function normalizeItem(SimpleXMLElement $item): array
    {
        $title = trim(html_entity_decode((string) $item->title, ENT_QUOTES | ENT_HTML5));
        $link = trim((string) $item->link);
        $guid = trim((string) ($item->guid ?? ''));
        $description = trim(html_entity_decode((string) ($item->description ?? ''), ENT_QUOTES | ENT_HTML5));
        $pubDate = trim((string) ($item->pubDate ?? ''));

        if ($link !== '' && str_starts_with($link, 'http://')) {
            $link = 'https://'.substr($link, 7);
        }

        $citation = null;
        if (preg_match('/\[(\d{4})\]\s+FCA(?:FC)?\s+\d+/i', $title, $m)) {
            $citation = trim($m[0]);
        }

        $decisionDate = null;
        if ($pubDate !== '') {
            try {
                $decisionDate = Carbon::parse($pubDate)->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        }

        $externalKey = $citation
            ?: ($guid !== '' ? md5($guid) : md5($title.$link));
        $parties = $this->parseParties($this->titleWithoutCitation($title));

        return [
            'external_id' => 'fca-'.$externalKey,
            'source' => 'fedcourt-au',
            'title' => $title !== '' ? $title : 'Untitled',
            'case_number' => null,
            'citation' => $citation,
            'neutral_citation' => $citation,
            'case_type' => $this->inferCaseType($title, $description),
            'decision_date' => $decisionDate,
            'author_judge' => null,
            'bench_json' => null,
            'judgment_url' => $link !== '' ? $link : null,
            'pdf_url' => null,
            'court_name' => 'Federal Court of Australia',
            'ai_summary' => $description !== '' ? mb_substr(strip_tags($description), 0, 500) : null,
            'keywords_json' => null,
            'party_one_name' => $parties[0],
            'party_two_name' => $parties[1],
            'full_text' => null,
            'is_demo' => false,
            'disposition' => null,
        ];
    }

    private function titleWithoutCitation(string $title): string
    {
        return trim((string) preg_replace('/\s*\[\d{4}\]\s+FCA(?:FC)?\s+\d+\s*$/i', '', $title));
    }

    private function inferCaseType(string $title, string $description): string
    {
        $blob = strtolower($title.' '.$description);

        return match (true) {
            str_contains($blob, 'criminal') || str_contains($blob, 'extradition') => 'criminal',
            str_contains($blob, 'family') || str_contains($blob, 'migration') && str_contains($blob, 'child') => 'family',
            str_contains($blob, 'tax') || str_contains($blob, 'taxation') => 'tax',
            str_contains($blob, 'appeal') || str_contains($blob, 'fca fc') => 'appeal',
            str_contains($blob, 'corporations') || str_contains($blob, 'insolvency') => 'civil',
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

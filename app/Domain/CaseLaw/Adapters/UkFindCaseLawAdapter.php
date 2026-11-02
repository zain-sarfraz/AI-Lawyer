<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use App\Domain\CaseLaw\UkJudgmentCounselParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * National Archives Find Case Law public Atom feed.
 *
 * @see https://caselaw.nationalarchives.gov.uk/open-justice-licence
 * @see https://nationalarchives.github.io/ds-find-caselaw-docs/public
 */
class UkFindCaseLawAdapter implements CaseSourceAdapter
{
    private const ATOM = 'http://www.w3.org/2005/Atom';

    private const TNA = 'https://caselaw.nationalarchives.gov.uk';

    public function search(array $params): iterable
    {
        if (! config('case_law.findcaselaw.enabled')) {
            throw new RuntimeException(
                'Find Case Law adapter is disabled. Set FINDCASELAW_ENABLED=true to use the public Atom API under the Open Justice Licence.'
            );
        }

        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $page = 1;
        $fetched = 0;
        $base = rtrim((string) config('case_law.findcaselaw.base_url', 'https://caselaw.nationalarchives.gov.uk'), '/');

        while ($fetched < $limit) {
            $xml = $this->fetchAtomPage($base, $page);
            $xml->registerXPathNamespace('a', self::ATOM);
            $xml->registerXPathNamespace('tna', self::TNA);

            $entries = $xml->xpath('//a:entry') ?: [];
            if ($entries === []) {
                break;
            }

            foreach ($entries as $entry) {
                if ($fetched >= $limit) {
                    break 2;
                }

                $entry->registerXPathNamespace('a', self::ATOM);
                $entry->registerXPathNamespace('tna', self::TNA);

                $row = $this->normalizeEntry($entry, $base);
                if (($row['title'] ?? '') === '' || ($row['title'] ?? '') === 'Untitled') {
                    continue;
                }

                if (! empty($params['enrich']) || ! empty($params['enrich_counsel'])) {
                    $row = $this->enrichFromXml($row);
                }

                yield $row;
                $fetched++;
            }

            $next = $xml->xpath('//a:link[@rel="next"]');
            if ($next === [] || $next === false) {
                break;
            }
            $page++;
        }
    }

    public function fetch(string $externalId): array
    {
        if (! config('case_law.findcaselaw.enabled')) {
            throw new RuntimeException('Find Case Law adapter is disabled (FINDCASELAW_ENABLED=false).');
        }

        foreach ($this->search(['limit' => 200]) as $row) {
            if (($row['external_id'] ?? '') === $externalId) {
                return $row;
            }
        }

        throw new RuntimeException("UK Find Case Law entry not found: {$externalId}");
    }

    private function fetchAtomPage(string $base, int $page): SimpleXMLElement
    {
        $url = $base.'/atom.xml';

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('case_law.fetch.user_agent'),
                'Accept' => 'application/atom+xml, application/xml, text/xml, */*',
            ])
                ->timeout(60)
                ->retry(
                    (int) config('case_law.fetch.retry_times', 3),
                    (int) config('case_law.fetch.retry_sleep_ms', 1500)
                )
                ->get($url, [
                    'order' => '-date',
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('HTTP '.$response->status());
            }

            return new SimpleXMLElement($response->body());
        } catch (Throwable $e) {
            Log::error('findcaselaw.atom_failed', ['error' => $e->getMessage(), 'page' => $page]);
            throw $e;
        }
    }

    /** @return array<string, mixed> */
    private function normalizeEntry(SimpleXMLElement $entry, string $base): array
    {
        $title = trim(html_entity_decode($this->xpathString($entry, 'a:title'), ENT_QUOTES | ENT_HTML5));
        $courtAuthor = trim($this->xpathString($entry, 'a:author/a:name'));
        $published = $this->xpathString($entry, 'a:published');

        $decisionDate = null;
        if ($published !== '') {
            try {
                $decisionDate = Carbon::parse($published)->toDateString();
            } catch (Throwable) {
                $decisionDate = null;
            }
        }

        $judgmentUrl = $this->xpathString($entry, 'a:link[@rel="alternate" and (not(@type) or @type="")]/@href');
        if ($judgmentUrl === '') {
            $judgmentUrl = $this->xpathString($entry, 'a:link[@rel="alternate"][1]/@href');
        }
        $pdfUrl = $this->xpathString($entry, 'a:link[@type="application/pdf"]/@href') ?: null;

        $citation = $this->xpathString($entry, 'tna:identifier[@type="ukncn"]') ?: null;
        $slug = $this->xpathString($entry, 'tna:identifier[@type="ukncn"]/@slug') ?: null;
        $uuid = $this->xpathString($entry, 'tna:uri') ?: null;

        if (! $judgmentUrl && $slug) {
            $judgmentUrl = $base.'/'.$slug;
        }
        // Prefer HTML judgment page over data.xml / pdf.
        if ($judgmentUrl && (str_ends_with($judgmentUrl, '/data.xml') || str_ends_with($judgmentUrl, '.pdf'))) {
            $judgmentUrl = $slug ? $base.'/'.$slug : $judgmentUrl;
        }

        $externalId = $slug ?: ($uuid ?: md5($title.$published));
        $parties = $this->parseParties($title);
        $xmlUrl = $this->xpathString($entry, 'a:link[@type="application/akn+xml"]/@href')
            ?: ($slug ? $base.'/'.$slug.'/data.xml' : null);

        return [
            'external_id' => 'fcl-'.$externalId,
            'source' => 'findcaselaw',
            'title' => $this->titleCaseParties($title !== '' ? $title : 'Untitled'),
            'citation' => $citation,
            'neutral_citation' => $citation,
            'case_type' => $this->inferCaseType($title, $courtAuthor, $citation ?? ''),
            'decision_date' => $decisionDate,
            'author_judge' => null,
            'bench_json' => null,
            'counsel_json' => null,
            'judgment_url' => $judgmentUrl ?: null,
            'pdf_url' => $pdfUrl,
            'xml_url' => $xmlUrl,
            'court_name' => $this->mapCourtName($courtAuthor),
            'ai_summary' => null,
            'party_one_name' => $parties[0],
            'party_two_name' => $parties[1],
            'full_text' => null,
            'is_demo' => false,
            'disposition' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function enrichFromXml(array $row): array
    {
        $xmlUrl = $row['xml_url'] ?? null;
        if (! is_string($xmlUrl) || $xmlUrl === '') {
            $judgmentUrl = (string) ($row['judgment_url'] ?? '');
            if ($judgmentUrl !== '' && ! str_ends_with($judgmentUrl, '/data.xml')) {
                $xmlUrl = rtrim($judgmentUrl, '/').'/data.xml';
            }
        }
        if (! is_string($xmlUrl) || $xmlUrl === '') {
            return $row;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('case_law.fetch.user_agent'),
                'Accept' => 'application/xml, text/xml, */*',
            ])->timeout(45)->get($xmlUrl);

            if (! $response->successful()) {
                return $row;
            }

            $xml = $response->body();
            $counsel = (new UkJudgmentCounselParser)->parse($xml);
            if ($counsel !== []) {
                $row['counsel_json'] = $counsel;
            }

            // Judges often appear as TLCPerson with judge- ids.
            if (preg_match_all('/<TLCPerson[^>]*eId="judge-[^"]*"[^>]*showAs="([^"]+)"/i', $xml, $m)) {
                $bench = array_values(array_unique(array_map(
                    static fn ($n) => html_entity_decode($n, ENT_QUOTES | ENT_HTML5),
                    $m[1]
                )));
                if ($bench !== []) {
                    $row['bench_json'] = $bench;
                    $row['author_judge'] = $bench[0];
                }
            }
        } catch (Throwable $e) {
            Log::warning('findcaselaw.xml_enrich_failed', [
                'url' => $xmlUrl,
                'error' => $e->getMessage(),
            ]);
        }

        unset($row['xml_url']);

        return $row;
    }

    private function xpathString(SimpleXMLElement $node, string $query): string
    {
        $result = $node->xpath($query);
        if ($result === false || $result === []) {
            return '';
        }

        return trim((string) $result[0]);
    }

    private function mapCourtName(string $author): string
    {
        $a = strtolower($author);

        return match (true) {
            str_contains($a, 'supreme court') => 'Supreme Court of the United Kingdom',
            str_contains($a, 'court of appeal') => 'Court of Appeal',
            str_contains($a, 'upper tribunal') => 'Upper Tribunal',
            str_contains($a, 'first-tier') || str_contains($a, 'first tier') || str_contains($a, 'ukftt') => 'Upper Tribunal',
            str_contains($a, 'court of protection') || str_contains($a, 'ewcop') => 'High Court',
            str_contains($a, 'high court') || str_contains($a, 'ewhc') => 'High Court',
            default => $author !== '' ? $author : 'High Court',
        };
    }

    private function inferCaseType(string $title, string $court, string $citation): string
    {
        $blob = strtolower($title.' '.$court.' '.$citation);

        return match (true) {
            str_contains($blob, 'crim') || str_contains($blob, 'ewca crim') => 'criminal',
            str_contains($blob, 'family') || str_contains($blob, 'ewfc') || str_contains($blob, 'ewhc fam') => 'family',
            str_contains($blob, 'tax') || str_contains($blob, 'ukut') => 'tax',
            str_contains($blob, 'admin') || str_contains($blob, 'judicial review') => 'writ',
            str_contains($blob, 'appeal') || str_contains($blob, 'ewca') => 'appeal',
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

    private function titleCaseParties(string $title): string
    {
        if ($title === strtoupper($title) && strlen($title) > 4) {
            return mb_convert_case(mb_strtolower($title), MB_CASE_TITLE, 'UTF-8');
        }

        return $title;
    }
}

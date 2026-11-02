<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sindh High Court public caselaw pages only (home.php latest list).
 * No login automation, credential guessing, or auth bypass.
 */
class SindhHighCourtAdapter implements CaseSourceAdapter
{
    public function search(array $params): iterable
    {
        $limit = max(1, (int) ($params['limit'] ?? config('case_law.fetch.max_requests_per_run', 25)));
        $html = $this->fetchHomeHtml();
        $count = 0;

        foreach ($this->parseReferenceBlocks($html) as $row) {
            yield $row;
            $count++;
            if ($count >= $limit) {
                break;
            }
        }
    }

    public function fetch(string $externalId): array
    {
        foreach ($this->search(['limit' => 200]) as $row) {
            if (($row['external_id'] ?? '') === $externalId) {
                return $row;
            }
        }

        throw new RuntimeException("Sindh case not found on public latest list: {$externalId}");
    }

    private function fetchHomeHtml(): string
    {
        $base = rtrim((string) config('case_law.sindh.base_url', 'https://caselaw.shc.gov.pk'), '/');
        $url = $base.'/caselaw/public/home.php';

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('case_law.fetch.user_agent'),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
                ->timeout(60)
                ->retry(
                    (int) config('case_law.fetch.retry_times', 3),
                    (int) config('case_law.fetch.retry_sleep_ms', 1500)
                )
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('HTTP '.$response->status());
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::error('sindh.adapter.fetch_failed', ['error' => $e->getMessage(), 'url' => $url]);
            throw $e;
        }
    }

    /** @return iterable<int, array<string, mixed>> */
    private function parseReferenceBlocks(string $html): iterable
    {
        if (! preg_match_all('/<textarea class="reference"[^>]*>(.*?)<\/textarea>/is', $html, $matches)) {
            return;
        }

        $seen = [];
        foreach ($matches[1] as $raw) {
            $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';
            if ($text === '') {
                continue;
            }

            if (! preg_match('/SHC Citation:\s*(SHC-\d+)/i', $text, $idMatch)) {
                continue;
            }

            $externalId = strtoupper($idMatch[1]);
            if (isset($seen[$externalId])) {
                continue;
            }
            $seen[$externalId] = true;

            $judgmentUrl = null;
            if (preg_match('/Order:\s*(https?:\/\/\S+)/i', $text, $urlMatch)) {
                $judgmentUrl = html_entity_decode(rtrim($urlMatch[1], " \t\n\r\0\x0B,\"'"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Prefer https when the site still emits http://shc.gov.pk/...
                $judgmentUrl = preg_replace('#^http://#i', 'https://', $judgmentUrl) ?? $judgmentUrl;
            }

            $citation = null;
            if (preg_match('/CITATION:\s*([^S]+?)(?:\s+SHC Citation:|$)/i', $text, $citMatch)) {
                $citation = trim($citMatch[1]);
            }

            $title = $text;
            if (preg_match('/^(.*?)\s+CITATION:/i', $text, $titleMatch)) {
                $title = trim($titleMatch[1]);
            }

            $caseNumber = null;
            if (preg_match('/^((?:Const\.?\s*P\.?|Criminal Appeal|Cr\.?\s*Bail|I\.T\.R\.A|C\.?P\.?|Suit)[^V]*?\d+\/\d+)/i', $title, $noMatch)) {
                $caseNumber = trim($noMatch[1]);
            }

            $caseType = $this->inferCaseType($title);
            $decisionDate = null;
            // Order dates on the page are unreliable in the clipboard blob; leave null unless clear.

            yield [
                'external_id' => $externalId,
                'source' => 'sindh-high-court',
                'title' => $title,
                'case_number' => $caseNumber,
                'citation' => $citation,
                'neutral_citation' => $citation,
                'case_type' => $caseType,
                'decision_date' => $decisionDate,
                'judgment_url' => $judgmentUrl,
                'pdf_url' => null,
                'court_name' => 'Sindh High Court',
                'full_text' => null,
                'is_demo' => false,
            ];
        }
    }

    private function inferCaseType(string $title): string
    {
        $t = strtolower($title);

        return match (true) {
            str_contains($t, 'criminal') || str_contains($t, 'cr.bail') || str_contains($t, 'cr. bail') => 'criminal',
            str_contains($t, 'const') || str_contains($t, 'c.p.') => 'writ',
            str_contains($t, 'i.t.r') || str_contains($t, 'tax') => 'tax',
            str_contains($t, 'appeal') => 'appeal',
            str_contains($t, 'family') || str_contains($t, 'guardian') => 'family',
            default => 'civil',
        };
    }
}

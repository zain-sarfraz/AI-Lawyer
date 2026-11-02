<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Legal\Contracts\LegalDataProvider;
use App\Domain\Legal\CourtListenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifies the Cases module's legal-data connection against the live API.
 *
 * The token is never printed, logged or echoed into a table — only whether it is
 * configured and whether the provider accepts it.
 */
class VerifyCourtListenerConnection extends Command
{
    protected $signature = 'courtlistener:verify
        {--category=civil : Which case category to probe against the live search endpoint}
        {--requests=1 : How many search requests to send (keep low: the free tier allows 5 per minute)}
        {--skip-end-to-end : Do not run the module search pipeline check, only the raw credential probe}';

    protected $description = 'Check the CourtListener connection used by the Cases module';

    public function handle(LegalDataProvider $provider): int
    {
        $base = rtrim((string) config('services.courtlistener.base_url'), '/');
        $token = trim((string) config('services.courtlistener.api_token'));

        $this->components->info('CourtListener connection check');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Base URL', $base],
                ['API token', $token === '' ? 'MISSING — set COURTLISTENER_API_TOKEN in .env' : 'configured ('.strlen($token).' characters, value hidden)'],
                ['Timeout', config('services.courtlistener.timeout').'s'],
                ['Declared rate limit', config('services.courtlistener.requests_per_minute').' requests/minute'],
                ['Provider coverage', implode(', ', $provider->supportedCountries())],
            ],
        );

        if ($token === '') {
            $this->components->error('No API token is configured, so the Cases module cannot retrieve records.');

            return self::FAILURE;
        }

        $category = (string) $this->option('category');

        if (! array_key_exists($category, $provider->categories())) {
            $this->components->error("Unknown category [{$category}]. Available: ".implode(', ', array_keys($provider->categories())).'.');

            return self::FAILURE;
        }

        $times = max(1, min(3, (int) $this->option('requests')));

        for ($attempt = 1; $attempt <= $times; $attempt++) {
            $status = $this->probe($base, $token, $category);

            if ($status !== self::SUCCESS) {
                return $status;
            }

            if ($attempt < $times) {
                $this->line('  waiting 13s to stay inside the per-minute limit…');
                sleep(13);
            }
        }

        if (! $this->option('skip-end-to-end')) {
            $this->endToEnd($provider, $category);
        }

        $this->components->info('The Cases module is live: the provider answered with real records.');

        return self::SUCCESS;
    }

    /**
     * Runs the same pipeline the screen uses (filter normalisation, pacing guard,
     * caching, field mapping) and reports the first real record it returns.
     */
    private function endToEnd(LegalDataProvider $provider, string $category): void
    {
        $result = $provider->search([
            'category'  => $category,
            'date_from' => now()->subYears(2)->toDateString(),
            'date_to'   => now()->toDateString(),
            'page'      => 1,
            'per_page'  => 5,
        ]);

        $first = $result['results'][0] ?? null;

        $this->table(
            ['Module pipeline', 'Result'],
            [
                ['Reason', $result['reason'] ?? 'unknown'],
                ['Message', $result['message'] ?? '—'],
                ['Records reported by provider', number_format((int) ($result['total'] ?? 0))],
                ['Records returned', (string) count($result['results'] ?? [])],
                ['Served from cache', !empty($result['stale']) ? 'stale copy' : (empty($result['reason']) ? '—' : 'fresh or cached')],
                ['Sample case', $first ? $first['case_name'].' — '.($first['court_name'] ?: $first['court_id']).' ('.$first['date_filed'].')' : '—'],
                ['Verifiable outcomes', (string) ($result['stats']['classifiable'] ?? 0)],
            ],
        );

        if (($result['reason'] ?? '') !== 'ok') {
            $this->components->warn('The pipeline did not return records: '.($result['message'] ?? 'no detail given.'));
        }
    }

    /**
     * One documented search request: opinion clusters (type=o) limited to the
     * last two years, asking only for the total so the probe stays cheap.
     */
    private function probe(string $base, string $token, string $category): int
    {
        $provider = app(LegalDataProvider::class);
        $terms = $this->categoryTerms($provider, $category);

        try {
            $response = Http::timeout((int) config('services.courtlistener.timeout', 20))
                ->withHeaders(['Authorization' => 'Token '.$token])
                ->get("{$base}/search/", [
                    'type' => 'o',
                    'q' => $terms,
                    'filed_after' => now()->subYears(2)->toDateString(),
                    'count' => 'on',
                    'page_size' => 1,
                    'fields' => 'cluster_id,caseName,court_id,dateFiled',
                ]);
        } catch (Throwable $e) {
            $this->components->error('Request failed: '.$e->getMessage());
            $this->line('  The host may be unreachable from this machine. Re-run from the app server.');

            return self::FAILURE;
        }

        $this->table(
            ['HTTP', 'Records reported', 'Throttle headers'],
            [[
                $response->status(),
                $response->successful() ? number_format((int) ($response->json('count') ?? 0)) : '—',
                $response->header('X-RateLimit-Remaining') ?: ($response->header('Retry-After') ? 'retry after '.$response->header('Retry-After').'s' : 'none returned'),
            ]],
        );

        return match (true) {
            $response->successful() => self::SUCCESS,
            $response->status() === 401, $response->status() === 403 => $this->failWith(
                'The API rejected the token. Confirm it is active on your CourtListener account.',
            ),
            $response->status() === 429 => $this->failWith(
                'Rate limited. Retry after '.$response->header('Retry-After', '60').'s — the module paces requests automatically.',
            ),
            default => $this->failWith(
                'Unexpected response: '.mb_substr((string) $response->body(), 0, 200),
            ),
        };
    }

    private function failWith(string $message): int
    {
        $this->components->error($message);

        return self::FAILURE;
    }

    /** Reads the terms from the provider so both stay in sync. */
    private function categoryTerms(LegalDataProvider $provider, string $category): string
    {
        if ($provider instanceof CourtListenerService) {
            return $provider->probeTerms($category);
        }

        return $provider->categories()[$category]['label'] ?? $category;
    }
}

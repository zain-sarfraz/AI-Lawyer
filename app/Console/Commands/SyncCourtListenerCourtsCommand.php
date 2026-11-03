<?php

namespace App\Console\Commands;

use App\Models\Court;
use App\Models\Jurisdiction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncCourtListenerCourtsCommand extends Command
{
    protected $signature = 'courts:sync-courtlistener
        {--page-size=100 : CourtListener page size}
        {--activate : Set is_active=true on jurisdictions that receive a mapped court}';

    protected $description = 'Fetch CourtListener /courts/ and map state supreme courts onto US-{STATE} jurisdictions';

    public function handle(): int
    {
        if (! config('case_law.courtlistener.enabled')) {
            $this->error('COURTLISTENER_ENABLED is false.');

            return self::FAILURE;
        }
        $token = (string) config('case_law.courtlistener.api_token', '');
        if ($token === '') {
            $this->error('COURTLISTENER_API_TOKEN is missing.');

            return self::FAILURE;
        }

        $base = rtrim((string) config('case_law.courtlistener.base_url'), '/');
        $url = $base.'/courts/?page_size='.(int) $this->option('page-size');
        $mapped = 0;
        $unmapped = [];

        while ($url) {
            $response = Http::withHeaders([
                'Authorization' => 'Token '.$token,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if (! $response->successful()) {
                $this->error('HTTP '.$response->status().' from CourtListener courts endpoint.');

                return self::FAILURE;
            }

            $json = $response->json();
            foreach (($json['results'] ?? []) as $court) {
                $id = (string) ($court['id'] ?? '');
                $name = (string) ($court['full_name'] ?? $court['short_name'] ?? $id);
                $jurisdictionCode = $this->mapJurisdiction($court);

                if (! $jurisdictionCode) {
                    $unmapped[] = "{$id}\t{$name}";
                    continue;
                }

                $j = Jurisdiction::query()->where('code', $jurisdictionCode)->first();
                if (! $j) {
                    $unmapped[] = "{$id}\t{$name}\t(missing {$jurisdictionCode})";
                    continue;
                }

                Court::query()->updateOrCreate(
                    [
                        'jurisdiction_id' => $j->id,
                        'external_court_id' => $id,
                    ],
                    [
                        'name' => $name,
                        'level' => $this->inferLevel($court, $name),
                    ]
                );
                $mapped++;

                if ($this->option('activate') && ! $j->is_active && $jurisdictionCode !== 'US-FED') {
                    $j->update(['is_active' => true]);
                }
            }

            $url = $json['next'] ?? null;
        }

        $this->info("Mapped courts: {$mapped}");
        $this->warn('Unmapped courts ('.count($unmapped).') — not guessing:');
        foreach (array_slice($unmapped, 0, 50) as $line) {
            $this->line('  '.$line);
        }
        if (count($unmapped) > 50) {
            $this->line('  … and '.(count($unmapped) - 50).' more');
        }

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $court */
    private function mapJurisdiction(array $court): ?string
    {
        $id = strtolower((string) ($court['id'] ?? ''));
        if (in_array($id, ['scotus', 'cadc', 'cafc'], true) || preg_match('/^ca\d{1,2}$/', $id)) {
            return 'US-FED';
        }

        // Prefer CourtListener jurisdiction string like "F" / "S" and state fields when present.
        $state = strtoupper((string) ($court['state'] ?? data_get($court, 'jurisdiction') ?? ''));
        if (strlen($state) === 2 && ctype::contains($id, 'supreme')) {
            return 'US-'.$state;
        }

        // e.g. cal / ny — only map when clearly a supreme court and state is known.
        $short = strtolower((string) ($court['short_name'] ?? ''));
        $full = strtolower((string) ($court['full_name'] ?? ''));
        if (! str_contains($full, 'supreme') && ! str_contains($short, 'supreme')) {
            return null;
        }

        $lookup = [
            'cal' => 'CA', 'ny' => 'NY', 'tex' => 'TX', 'fla' => 'FL', 'ill' => 'IL',
            'penn' => 'PA', 'ohio' => 'OH', 'ga' => 'GA', 'nc' => 'NC', 'mich' => 'MI',
            'nj' => 'NJ', 'va' => 'VA', 'wash' => 'WA', 'ariz' => 'AZ', 'mass' => 'MA',
            'tenn' => 'TN', 'ind' => 'IN', 'mo' => 'MO', 'md' => 'MD', 'wis' => 'WI',
            'minn' => 'MN', 'colo' => 'CO', 'ala' => 'AL', 'sc' => 'SC', 'la' => 'LA',
            'ky' => 'KY', 'ore' => 'OR', 'okla' => 'OK', 'conn' => 'CT', 'utah' => 'UT',
            'iowa' => 'IA', 'nev' => 'NV', 'ark' => 'AR', 'miss' => 'MS', 'kan' => 'KS',
            'nm' => 'NM', 'neb' => 'NE', 'idaho' => 'ID', 'hawaii' => 'HI', 'nh' => 'NH',
            'me' => 'ME', 'ri' => 'RI', 'mont' => 'MT', 'del' => 'DE', 'sd' => 'SD',
            'nd' => 'ND', 'alaska' => 'AK', 'vt' => 'VT', 'wyo' => 'WY', 'dc' => 'DC',
            'wv' => 'WV',
        ];
        foreach ($lookup as $needle => $st) {
            if ($id === $needle || str_starts_with($id, $needle) || str_contains($id, $needle.'-')) {
                return 'US-'.$st;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $court */
    private function inferLevel(array $court, string $name): string
    {
        $n = strtolower($name);
        if (str_contains($n, 'supreme')) {
            return 'supreme';
        }
        if (str_contains($n, 'appeal')) {
            return 'appeal';
        }

        return 'high';
    }
}

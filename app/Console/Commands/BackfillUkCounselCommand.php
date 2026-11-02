<?php

namespace App\Console\Commands;

use App\Domain\CaseLaw\UkJudgmentCounselParser;
use App\Models\CaseLawRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class BackfillUkCounselCommand extends Command
{
    protected $signature = 'cases:backfill-uk-counsel {--limit=50 : Max records to enrich}';

    protected $description = 'Fetch UK judgment XML and fill counsel_json / bench where missing';

    public function handle(UkJudgmentCounselParser $parser): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $query = CaseLawRecord::query()
            ->where('source', 'findcaselaw')
            ->whereNotNull('judgment_url')
            ->where(function ($q) {
                $q->whereNull('counsel_json')
                    ->orWhere('counsel_json', '[]')
                    ->orWhere('counsel_json', 'null');
            })
            ->orderByDesc('id')
            ->limit($limit);

        $updated = 0;
        foreach ($query->cursor() as $record) {
            $xmlUrl = rtrim((string) $record->judgment_url, '/').'/data.xml';
            try {
                $response = Http::withHeaders([
                    'User-Agent' => (string) config('case_law.fetch.user_agent'),
                    'Accept' => 'application/xml, text/xml, */*',
                ])->timeout(45)->get($xmlUrl);

                if (! $response->successful()) {
                    $this->warn("HTTP {$response->status()} for {$record->id}");
                    continue;
                }

                $xml = $response->body();
                $counsel = $parser->parse($xml);
                $bench = [];
                if (preg_match_all('/<TLCPerson[^>]*eId="judge-[^"]*"[^>]*showAs="([^"]+)"/i', $xml, $m)) {
                    $bench = array_values(array_unique(array_map(
                        static fn ($n) => html_entity_decode($n, ENT_QUOTES | ENT_HTML5),
                        $m[1]
                    )));
                }

                if ($counsel === [] && $bench === []) {
                    continue;
                }

                $record->update([
                    'counsel_json' => $counsel !== [] ? $counsel : $record->counsel_json,
                    'bench_json' => $bench !== [] ? $bench : $record->bench_json,
                    'author_judge' => ($bench[0] ?? null) ?: $record->author_judge,
                ]);
                $updated++;
                $this->line("Updated #{$record->id} counsel=".count($counsel).' bench='.count($bench));
            } catch (Throwable $e) {
                $this->error("#{$record->id}: ".$e->getMessage());
            }

            usleep(150000);
        }

        $this->info("Done. Updated {$updated} records.");

        return self::SUCCESS;
    }
}

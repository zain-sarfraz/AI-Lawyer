<?php

namespace App\Console\Commands;

use App\Domain\CaseLaw\CaseLawUpserter;
use App\Models\Jurisdiction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportCourtListenerBulkCommand extends Command
{
    protected $signature = 'cases:import-courtlistener-bulk
        {path : Local CSV or JSON file}
        {--jurisdiction=US-FED : Target jurisdiction code}
        {--chunk=200 : Rows per chunk}
        {--limit=0 : Stop after N rows (0 = all)}';

    protected $description = 'Import CourtListener bulk dump (CSV/JSON) using config column map';

    public function handle(CaseLawUpserter $upserter): int
    {
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $code = strtoupper((string) $this->option('jurisdiction'));
        $jurisdiction = Jurisdiction::query()->where('code', $code)->first();
        if (! $jurisdiction) {
            $this->error("Unknown jurisdiction: {$code}");

            return self::FAILURE;
        }

        $map = (array) config('case_law.courtlistener.bulk_column_map', []);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $limit = (int) $this->option('limit');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $upserted = 0;
        $failed = 0;

        if ($ext === 'json') {
            $rows = json_decode(File::get($path), true);
            if (! is_array($rows)) {
                $this->error('JSON must be an array of objects.');

                return self::FAILURE;
            }
            if ($rows !== [] && is_array($rows[0] ?? null)) {
                $this->info('Detected columns: '.implode(', ', array_keys($rows[0])));
            }
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                foreach ($chunk as $row) {
                    if ($limit > 0 && $upserted >= $limit) {
                        break 2;
                    }
                    try {
                        $upserter->upsert($jurisdiction, $this->mapRow((array) $row, $map));
                        $upserted++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn($e->getMessage());
                    }
                }
            }
        } elseif ($ext === 'csv') {
            $handle = fopen($path, 'r');
            if (! $handle) {
                throw new RuntimeException('Unable to open CSV');
            }
            $headers = fgetcsv($handle);
            if (! is_array($headers)) {
                $this->error('CSV has no header row.');

                return self::FAILURE;
            }
            $headers = array_map(static fn ($h) => trim((string) $h), $headers);
            $this->info('Detected columns: '.implode(', ', $headers));
            $buffer = [];
            while (($data = fgetcsv($handle)) !== false) {
                if ($limit > 0 && $upserted >= $limit) {
                    break;
                }
                $row = [];
                foreach ($headers as $i => $header) {
                    $row[$header] = $data[$i] ?? null;
                }
                $buffer[] = $row;
                if (count($buffer) >= $chunkSize) {
                    foreach ($buffer as $r) {
                        try {
                            $upserter->upsert($jurisdiction, $this->mapRow($r, $map));
                            $upserted++;
                        } catch (\Throwable $e) {
                            $failed++;
                        }
                        if ($limit > 0 && $upserted >= $limit) {
                            break;
                        }
                    }
                    $buffer = [];
                }
            }
            foreach ($buffer as $r) {
                if ($limit > 0 && $upserted >= $limit) {
                    break;
                }
                try {
                    $upserter->upsert($jurisdiction, $this->mapRow($r, $map));
                    $upserted++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
            fclose($handle);
        } else {
            $this->error('Supported formats: .csv, .json');

            return self::FAILURE;
        }

        $this->info("Upserted: {$upserted}; failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $map
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array $map): array
    {
        $get = static function (string $key) use ($row, $map) {
            $col = $map[$key] ?? $key;

            return $row[$col] ?? null;
        };

        $external = (string) ($get('external_id') ?? '');
        if ($external === '') {
            throw new RuntimeException('Row missing external_id column');
        }

        $url = (string) ($get('judgment_url') ?? '');
        if ($url !== '' && str_starts_with($url, '/')) {
            $url = 'https://www.courtlistener.com'.$url;
        }

        return [
            'external_id' => 'cl-'.$external,
            'source' => 'courtlistener',
            'title' => (string) ($get('title') ?? 'Untitled'),
            'citation' => $get('citation'),
            'decision_date' => $get('decision_date'),
            'full_text' => $get('full_text'),
            'judgment_url' => $url !== '' ? $url : null,
            'external_court_id' => $get('court'),
            'is_demo' => false,
        ];
    }
}

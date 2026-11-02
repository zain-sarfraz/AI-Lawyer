<?php

namespace App\Console\Commands;

use App\Domain\CaseLaw\CaseLawUpserter;
use App\Models\Jurisdiction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Imports open dataset "Ibtehaj10/supreme-court-of-pak-judgments" from a local file.
 * Column names are not hardcoded — mapped via config('case_law.huggingface_scp.column_map').
 */
class ImportHuggingFaceScpCommand extends Command
{
    protected $signature = 'cases:import-hf-scp {path : Local JSON/CSV file path} {--limit=}';

    protected $description = 'Import Supreme Court of Pakistan judgments from a local HuggingFace export file';

    public function handle(CaseLawUpserter $upserter): int
    {
        $path = (string) $this->argument('path');
        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $jurisdiction = Jurisdiction::query()->where('code', 'PK-FED')->first();
        if (! $jurisdiction) {
            $this->error('PK-FED jurisdiction missing. Run JurisdictionSeeder.');

            return self::FAILURE;
        }

        $rows = $this->readRows($path);
        if ($rows === []) {
            $this->warn('No rows decoded.');

            return self::FAILURE;
        }

        $columns = array_keys($rows[0]);
        $this->info('Detected columns: '.implode(', ', $columns));

        $map = (array) config('case_law.huggingface_scp.column_map', []);
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $imported = 0;

        foreach ($rows as $row) {
            if ($limit !== null && $imported >= $limit) {
                break;
            }

            $payload = [
                'source' => 'huggingface-scp',
                'external_id' => (string) ($row[$map['external_id'] ?? 'id'] ?? uniqid('scp_', true)),
                'title' => (string) ($row[$map['title'] ?? 'title'] ?? 'Untitled'),
                'citation' => $row[$map['citation'] ?? 'citation'] ?? null,
                'decision_date' => $row[$map['decision_date'] ?? 'date'] ?? null,
                'full_text' => $row[$map['full_text'] ?? 'text'] ?? null,
                'source_url' => $row[$map['source_url'] ?? 'url'] ?? null,
                'court_name' => 'Supreme Court of Pakistan',
            ];

            $upserter->upsert($jurisdiction, $payload);
            $imported++;
        }

        $this->info("Imported {$imported} records into PK-FED.");

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function readRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'json') {
            $data = json_decode(File::get($path), true);
            if (isset($data[0]) && is_array($data[0])) {
                return $data;
            }
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }

            return [];
        }

        if ($ext === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return [];
            }
            $headers = fgetcsv($handle) ?: [];
            $rows = [];
            while (($line = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($headers, $line) ?: [];
            }
            fclose($handle);

            return $rows;
        }

        if ($ext === 'parquet') {
            $this->warn('Parquet requires an external reader. Convert to JSON/CSV first, or install a parquet library.');
        }

        return [];
    }
}

<?php

namespace App\Domain\Legal;

use App\Domain\Legal\Contracts\LegalDataProvider;
use Illuminate\Support\Facades\File;

/**
 * Pakistan case-law provider — Sindh High Court + Lahore High Court (Punjab).
 *
 * Serves curated provincial judgments from storage/app/legal/pakistan-cases.json.
 * No live scraping; records are filterable by province/court, category, and query.
 */
class PakistanCaseLawService implements LegalDataProvider
{
    private const CATEGORIES = [
        'civil' => [
            'label' => 'Civil',
            'description' => 'Suits, appeals and commercial disputes',
            'icon' => 'scales',
            'types' => ['Civil Suit', 'Regular Second Appeal', 'Constitution Petition'],
        ],
        'criminal' => [
            'label' => 'Criminal',
            'description' => 'Appeals, bail and prosecution matters',
            'icon' => 'shield',
            'types' => ['Criminal Appeal', 'Bail Application'],
        ],
        'family' => [
            'label' => 'Family',
            'description' => 'Custody, maintenance and family writs',
            'icon' => 'users',
            'types' => ['Family Appeal', 'Family Writ'],
        ],
        'employment' => [
            'label' => 'Employment',
            'description' => 'Labour and industrial relations',
            'icon' => 'briefcase',
            'types' => ['Labour Petition', 'Labour Writ'],
        ],
        'constitutional' => [
            'label' => 'Constitutional',
            'description' => 'Writs and constitution petitions',
            'icon' => 'book',
            'types' => ['Constitution Petition', 'Writ Petition'],
        ],
        'tax' => [
            'label' => 'Tax',
            'description' => 'Federal and provincial tax references',
            'icon' => 'chart',
            'types' => ['Tax Reference', 'Tax Writ'],
        ],
    ];

    private const COURTS = [
        ['id' => 'shc', 'name' => 'High Court of Sindh', 'jurisdiction' => 'Sindh', 'location' => 'Karachi'],
        ['id' => 'lhc', 'name' => 'Lahore High Court', 'jurisdiction' => 'Punjab', 'location' => 'Lahore'],
    ];

    public function providerName(): string
    {
        return 'Pakistan Case Law (Sindh & Punjab)';
    }

    public function supportedCountries(): array
    {
        return ['PK'];
    }

    public function isConfigured(): bool
    {
        return File::exists($this->datasetPath());
    }

    public function categories(): array
    {
        return collect(self::CATEGORIES)
            ->map(fn (array $c) => [
                'label' => $c['label'],
                'description' => $c['description'],
                'icon' => $c['icon'],
            ])
            ->all();
    }

    public function caseTypes(?string $category): array
    {
        $types = $category && isset(self::CATEGORIES[$category])
            ? self::CATEGORIES[$category]['types']
            : collect(self::CATEGORIES)->flatMap(fn (array $c) => $c['types'])->unique()->values()->all();

        return array_map(fn (string $t) => ['value' => $t, 'label' => $t], $types);
    }

    public function search(array $filters): array
    {
        $country = strtoupper(trim((string) ($filters['country'] ?? 'PK')));
        if ($country !== '' && $country !== 'PK') {
            return $this->empty('unsupported_country', "Pakistan Case Law only covers PK — not \"{$country}\".");
        }

        if (! $this->isConfigured()) {
            return $this->empty('unconfigured', 'Pakistan case dataset is missing.');
        }

        $rows = $this->all();
        $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $category = trim((string) ($filters['category'] ?? ''));
        $caseType = trim((string) ($filters['case_type'] ?? ''));
        $court = trim((string) ($filters['court'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        $filtered = array_values(array_filter($rows, function (array $row) use ($q, $category, $caseType, $court, $dateFrom, $dateTo) {
            if ($category !== '' && ($row['category'] ?? '') !== $category) {
                return false;
            }
            if ($caseType !== '' && strcasecmp((string) ($row['case_type'] ?? ''), $caseType) !== 0) {
                return false;
            }
            if ($court !== '') {
                $needle = mb_strtolower($court);
                $hay = mb_strtolower(($row['court_id'] ?? '').' '.($row['province'] ?? '').' '.($row['court_name'] ?? ''));
                if (! str_contains($hay, $needle)) {
                    return false;
                }
            }
            if ($dateFrom !== '' && ($row['date_filed'] ?? '') !== '' && $row['date_filed'] < $dateFrom) {
                return false;
            }
            if ($dateTo !== '' && ($row['date_filed'] ?? '') !== '' && $row['date_filed'] > $dateTo) {
                return false;
            }
            if ($q !== '') {
                $blob = mb_strtolower(implode(' ', [
                    $row['case_name'] ?? '',
                    $row['docket_number'] ?? '',
                    $row['citation'] ?? '',
                    $row['snippet'] ?? '',
                    $row['province'] ?? '',
                    $row['case_type'] ?? '',
                ]));
                if (! str_contains($blob, $q)) {
                    return false;
                }
            }

            return true;
        }));

        $total = count($filtered);
        $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);
        $records = array_map(fn (array $row) => $this->transform($row), $slice);

        return [
            'success' => true,
            'reason' => $records === [] ? 'empty' : 'ok',
            'message' => $records === [] ? 'No Sindh or Punjab judgments matched these filters.' : null,
            'results' => $records,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'page_size' => $perPage,
            'total_pages' => $pages,
            'has_next' => $page < $pages,
            'has_prev' => $page > 1,
            'stats' => $this->stats($filtered, $total),
            'provider' => $this->providerName(),
            'country' => 'PK',
            'stale' => false,
            'retry_after' => null,
            'fetched_at' => now()->toIso8601String(),
            'range' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ];
    }

    public function getCase(string $id): ?array
    {
        foreach ($this->all() as $row) {
            if ((string) ($row['id'] ?? '') === $id) {
                return $this->transform($row, detailed: true);
            }
        }

        return null;
    }

    public function countCategory(string $category, ?string $dateFrom = null, ?string $dateTo = null): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->search([
            'category' => $category,
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
            'per_page' => 1,
        ])['total'] ?? 0;
    }

    public function courts(): array
    {
        return self::COURTS;
    }

    /** @return list<array<string, mixed>> */
    private function all(): array
    {
        $raw = json_decode(File::get($this->datasetPath()), true);

        return is_array($raw) ? $raw : [];
    }

    private function datasetPath(): string
    {
        return storage_path('app/legal/pakistan-cases.json');
    }

    /** @param  array<string, mixed>  $row */
    private function transform(array $row, bool $detailed = false): array
    {
        $base = [
            'case_name' => (string) ($row['case_name'] ?? 'Untitled case'),
            'case_id' => (string) ($row['id'] ?? ''),
            'docket_number' => (string) ($row['docket_number'] ?? ''),
            'citation' => (string) ($row['citation'] ?? ''),
            'court_id' => (string) ($row['court_id'] ?? ''),
            'court_name' => (string) ($row['court_name'] ?? ''),
            'court_short' => (string) ($row['province'] ?? ''),
            'country' => 'PK',
            'jurisdiction' => (string) ($row['province'] ?? ''),
            'jurisdiction_code' => (string) ($row['province'] ?? ''),
            'category' => self::CATEGORIES[$row['category'] ?? '']['label'] ?? (string) ($row['category'] ?? ''),
            'nature_of_suit' => (string) ($row['case_type'] ?? ''),
            'posture' => '',
            'cause' => '',
            'status' => (string) ($row['status'] ?? ''),
            'date_filed' => (string) ($row['date_filed'] ?? ''),
            'decision_date' => (string) ($row['decision_date'] ?? ''),
            'snippet' => (string) ($row['snippet'] ?? ''),
            'outcome' => (string) ($row['outcome'] ?? 'unknown'),
            'absolute_url' => (string) ($row['url'] ?? ''),
            'provider' => $this->providerName(),
        ];

        if ($detailed) {
            $base['full_text'] = $base['snippet'];
        }

        return $base;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function stats(array $rows, int $total): array
    {
        $courts = [];
        $types = [];
        foreach ($rows as $row) {
            $c = (string) ($row['court_name'] ?? 'Unknown');
            $courts[$c] = ($courts[$c] ?? 0) + 1;
            $t = (string) ($row['case_type'] ?? 'Other');
            $types[$t] = ($types[$t] ?? 0) + 1;
        }

        return [
            'total_found' => $total,
            'analyzed' => count($rows),
            'outcomes' => ['won' => 0, 'lost' => 0, 'pending' => 0, 'unknown' => count($rows)],
            'classifiable' => 0,
            'win_pct' => null,
            'loss_pct' => null,
            'courts' => $courts,
            'case_types' => $types,
            'outcome_note' => 'Pakistan judgments are listed with court disposition text; party-relative win/loss is not claimed.',
        ];
    }

    private function empty(string $reason, string $message): array
    {
        return [
            'success' => false,
            'reason' => $reason,
            'message' => $message,
            'results' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
            'page_size' => 20,
            'total_pages' => 0,
            'has_next' => false,
            'has_prev' => false,
            'stats' => [
                'total_found' => 0,
                'analyzed' => 0,
                'outcomes' => ['won' => 0, 'lost' => 0, 'pending' => 0, 'unknown' => 0],
                'classifiable' => 0,
                'win_pct' => null,
                'loss_pct' => null,
                'courts' => [],
                'case_types' => [],
                'outcome_note' => $message,
            ],
            'provider' => $this->providerName(),
            'country' => 'PK',
            'stale' => false,
            'retry_after' => null,
            'fetched_at' => now()->toIso8601String(),
            'range' => ['date_from' => '', 'date_to' => ''],
        ];
    }
}

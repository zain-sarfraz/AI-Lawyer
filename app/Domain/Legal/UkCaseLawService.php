<?php

namespace App\Domain\Legal;

use App\Domain\Legal\Contracts\LegalDataProvider;
use Illuminate\Support\Facades\File;

/** United Kingdom case-law provider (curated England & Wales authorities). */
class UkCaseLawService implements LegalDataProvider
{
    private const CATEGORIES = [
        'civil' => ['label' => 'Civil', 'description' => 'Commercial and private law', 'icon' => 'scales', 'types' => ['Commercial Appeal', 'Data Protection Claim', 'Privacy / Misuse of Information']],
        'criminal' => ['label' => 'Criminal', 'description' => 'Appeals and criminal liability', 'icon' => 'shield', 'types' => ['Criminal Appeal']],
        'family' => ['label' => 'Family', 'description' => 'Children and family proceedings', 'icon' => 'users', 'types' => ['Family Appeal']],
        'employment' => ['label' => 'Employment', 'description' => 'Workers and employment status', 'icon' => 'briefcase', 'types' => ['Employment Appeal']],
        'constitutional' => ['label' => 'Constitutional', 'description' => 'Public law and judicial review', 'icon' => 'book', 'types' => ['Judicial Review']],
        'immigration' => ['label' => 'Immigration', 'description' => 'Asylum and immigration appeals', 'icon' => 'arrow-up-right', 'types' => ['Immigration Appeal']],
    ];

    public function providerName(): string
    {
        return 'UK Case Law (England & Wales)';
    }

    public function supportedCountries(): array
    {
        return ['GB'];
    }

    public function isConfigured(): bool
    {
        return File::exists($this->path());
    }

    public function categories(): array
    {
        return collect(self::CATEGORIES)->map(fn (array $c) => [
            'label' => $c['label'],
            'description' => $c['description'],
            'icon' => $c['icon'],
        ])->all();
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
        $country = strtoupper(trim((string) ($filters['country'] ?? 'GB')));
        if ($country !== '' && $country !== 'GB') {
            return $this->empty('unsupported_country', "UK Case Law only covers GB — not \"{$country}\".");
        }
        if (! $this->isConfigured()) {
            return $this->empty('unconfigured', 'UK case dataset is missing.');
        }

        $rows = json_decode(File::get($this->path()), true) ?: [];
        $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $category = trim((string) ($filters['category'] ?? ''));
        $caseType = trim((string) ($filters['case_type'] ?? ''));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        $filtered = array_values(array_filter($rows, function (array $row) use ($q, $category, $caseType) {
            if ($category !== '' && ($row['category'] ?? '') !== $category) {
                return false;
            }
            if ($caseType !== '' && strcasecmp((string) ($row['case_type'] ?? ''), $caseType) !== 0) {
                return false;
            }
            if ($q !== '') {
                $blob = mb_strtolower(implode(' ', [
                    $row['case_name'] ?? '', $row['citation'] ?? '', $row['snippet'] ?? '', $row['case_type'] ?? '',
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

        return [
            'success' => true,
            'reason' => $slice === [] ? 'empty' : 'ok',
            'message' => $slice === [] ? 'No UK judgments matched these filters.' : null,
            'results' => array_map(fn (array $row) => $this->transform($row), $slice),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'page_size' => $perPage,
            'total_pages' => $pages,
            'has_next' => $page < $pages,
            'has_prev' => $page > 1,
            'stats' => [
                'total_found' => $total,
                'analyzed' => count($slice),
                'outcomes' => ['won' => 0, 'lost' => 0, 'pending' => 0, 'unknown' => count($slice)],
                'classifiable' => 0,
                'win_pct' => null,
                'loss_pct' => null,
                'courts' => [],
                'case_types' => [],
                'outcome_note' => 'UK authorities listed for research reference; party-relative win/loss is not claimed.',
            ],
            'provider' => $this->providerName(),
            'country' => 'GB',
            'stale' => false,
            'retry_after' => null,
            'fetched_at' => now()->toIso8601String(),
            'range' => ['date_from' => '', 'date_to' => ''],
        ];
    }

    public function getCase(string $id): ?array
    {
        foreach (json_decode(File::get($this->path()), true) ?: [] as $row) {
            if ((string) ($row['id'] ?? '') === $id) {
                return $this->transform($row);
            }
        }

        return null;
    }

    public function countCategory(string $category, ?string $dateFrom = null, ?string $dateTo = null): ?int
    {
        return $this->isConfigured()
            ? ($this->search(['category' => $category, 'per_page' => 1])['total'] ?? 0)
            : null;
    }

    public function courts(): array
    {
        return [
            ['id' => 'uksc', 'name' => 'Supreme Court of the United Kingdom', 'jurisdiction' => 'UK', 'location' => 'London'],
            ['id' => 'ewca', 'name' => 'Court of Appeal (Civil Division)', 'jurisdiction' => 'England & Wales', 'location' => 'London'],
            ['id' => 'ewhc', 'name' => 'High Court of Justice', 'jurisdiction' => 'England & Wales', 'location' => 'London'],
            ['id' => 'ukut', 'name' => 'Upper Tribunal (IAC)', 'jurisdiction' => 'UK', 'location' => 'London'],
        ];
    }

    private function path(): string
    {
        return storage_path('app/legal/uk-cases.json');
    }

    private function transform(array $row): array
    {
        return [
            'case_name' => (string) ($row['case_name'] ?? 'Untitled case'),
            'case_id' => (string) ($row['id'] ?? ''),
            'docket_number' => (string) ($row['docket_number'] ?? ''),
            'citation' => (string) ($row['citation'] ?? ''),
            'court_id' => (string) ($row['court_id'] ?? ''),
            'court_name' => (string) ($row['court_name'] ?? ''),
            'court_short' => (string) ($row['province'] ?? ''),
            'country' => 'GB',
            'jurisdiction' => (string) ($row['province'] ?? 'England & Wales'),
            'jurisdiction_code' => 'GB',
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
            'country' => 'GB',
            'stale' => false,
            'retry_after' => null,
            'fetched_at' => now()->toIso8601String(),
            'range' => ['date_from' => '', 'date_to' => ''],
        ];
    }
}

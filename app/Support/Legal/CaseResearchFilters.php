<?php

namespace App\Support\Legal;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Query filters for the Cases (legal research) module.
 *
 * Normalises and validates user input into the provider filter contract:
 * everything unsupported, malformed or empty is coerced away rather than
 * forwarded to the legal-data API.
 */
final class CaseResearchFilters
{
    /** Preset windows measured against a record's decision date. */
    public const DATE_RANGES = ['1m', '3m', '6m', '1y', '2y', 'custom'];

    /** Labels shown in the date filter, in DATE_RANGES order. */
    public const DATE_RANGE_LABELS = [
        '1m' => 'Last month',
        '3m' => 'Previous months (3)',
        '6m' => 'Last 6 months',
        '1y' => 'Last 1 year',
        '2y' => 'Last 2 years',
        'custom' => 'Custom date range',
    ];

    private const RANGE_MONTHS = [
        '1m' => 1,
        '3m' => 3,
        '6m' => 6,
        '1y' => 12,
        '2y' => 24,
    ];

    private function __construct(
        public readonly string $category = '',
        public readonly string $query = '',
        public readonly string $caseType = '',
        public readonly string $court = '',
        public readonly string $country = '',
        public readonly string $dateRange = '2y',
        public readonly string $dateFrom = '',
        public readonly string $dateTo = '',
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $outcome = '',
    ) {
    }

    /**
     * Build filters from an HTTP request.
     *
     * @param  list<string>  $supportedCountries ISO alpha-2 codes with a live provider
     */
    public static function fromRequest(Request $request, array $supportedCountries = [], ?string $signupCountry = null): self
    {
        $dateRange = in_array((string) $request->input('date_range'), self::DATE_RANGES, true)
            ? (string) $request->input('date_range')
            : '2y';

        [$from, $to] = self::resolveWindow($dateRange, $request);

        return new self(
            category: self::key((string) $request->input('category', '')),
            query: mb_substr(trim((string) $request->input('q', '')), 0, 180),
            caseType: self::phrase((string) $request->input('case_type', '')),
            court: self::courtIds((string) $request->input('court', '')),
            country: self::country((string) $request->input('country', ''), $supportedCountries, $signupCountry),
            dateRange: $dateRange,
            dateFrom: $from,
            dateTo: $to,
            page: min(100, max(1, (int) $request->input('page', 1))),
            perPage: min(50, max(5, (int) $request->input('per_page', 20))),
            outcome: in_array((string) $request->input('outcome'), ['won', 'lost', 'pending', 'unknown'], true)
                ? (string) $request->input('outcome')
                : '',
        );
    }

    /** @return array<string, mixed> filters in the provider contract shape */
    public function toArray(): array
    {
        return [
            'category'  => $this->category,
            'q'         => $this->query,
            'case_type' => $this->caseType,
            'court'     => $this->court,
            'country'   => $this->country,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
            'page'      => $this->page,
            'per_page'  => $this->perPage,
        ];
    }

    /** @return array<string, mixed> values the page needs to rehydrate its controls */
    public function toView(): array
    {
        return [
            'category'   => $this->category,
            'q'          => $this->query,
            'case_type'  => $this->caseType,
            'court'      => $this->court,
            'country'    => $this->country,
            'date_range' => $this->dateRange,
            'date_from'  => $this->dateFrom,
            'date_to'    => $this->dateTo,
            'page'       => $this->page,
            'per_page'   => $this->perPage,
            'outcome'    => $this->outcome,
        ];
    }

    public function label(): string
    {
        return self::DATE_RANGE_LABELS[$this->dateRange] ?? self::DATE_RANGE_LABELS['2y'];
    }

    /* -----------------------------------------------------------------
     |  Normalisation
     | ----------------------------------------------------------------- */

    /**
     * @return array{0: string, 1: string} [date_from, date_to] as YYYY-MM-DD
     */
    private static function resolveWindow(string $range, Request $request): array
    {
        $today = Carbon::today();

        if ($range === 'custom') {
            $from = self::date($request->input('date_from'));
            $to   = self::date($request->input('date_to'));

            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from]; // never silently return nothing
            }

            return [$from ?? '', $to ?? $today->toDateString()];
        }

        return [
            $today->copy()->subMonths(self::RANGE_MONTHS[$range])->toDateString(),
            $today->toDateString(),
        ];
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $date && $date->lessThanOrEqualTo(Carbon::today()) ? $value : null;
    }

    private static function key(string $value): string
    {
        return preg_replace('/[^a-z_]/', '', strtolower(trim($value))) ?? '';
    }

    private static function phrase(string $value): string
    {
        $value = preg_replace('/[^\p{L}\p{N}\s&\'\/.-]/u', '', trim($value)) ?? '';

        return mb_substr(trim($value), 0, 60);
    }

    private static function courtIds(string $value): string
    {
        $ids = collect(explode(',', strtolower($value)))
            ->map(fn (string $id) => preg_replace('/[^a-z0-9_-]/', '', trim($id)) ?? '')
            ->filter()
            ->take(5)
            ->values()
            ->all();

        return implode(',', $ids);
    }

    /**
     * Country selection: accept the request value, otherwise fall back to the
     * signup country — but only when a provider actually covers it.
     *
     * @param  list<string>  $supportedCountries
     */
    private static function country(string $requested, array $supportedCountries, ?string $signupCountry): string
    {
        $normalised = self::toIso($requested);

        if ($normalised !== '') {
            return $normalised;
        }

        $signup = self::toIso((string) $signupCountry);

        if ($signup !== '' && in_array($signup, $supportedCountries, true)) {
            return $signup;
        }

        return '';
    }

    private static function toIso(string $value): string
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Z]{2}$/', $value)) {
            return $value;
        }

        return match ($value) {
            'USA', 'UNITED STATES', 'UNITED STATES OF AMERICA', 'US' => 'US',
            'PAKISTAN', 'PK' => 'PK',
            'UK', 'GB', 'UNITED KINGDOM', 'GREAT BRITAIN', 'ENGLAND' => 'GB',
            default => '',
        };
    }
}

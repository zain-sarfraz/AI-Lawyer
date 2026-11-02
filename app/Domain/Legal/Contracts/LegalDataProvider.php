<?php

namespace App\Domain\Legal\Contracts;

/**
 * Contract for country-specific legal-data providers.
 *
 * CourtListener covers United States case law only. Additional providers
 * (other jurisdictions) can implement this same contract and be swapped in
 * without touching the Case Research module.
 *
 * Search result shape (returned by {@see self::search()}) is a plain array:
 *
 *   success  bool    whether the provider call completed
 *   reason   string  ok|empty|unconfigured|unsupported_country|rate_limited|error
 *   message  ?string human-readable explanation for non-ok reasons
 *   results  list<array> normalised case records
 *   total    int     total matching records reported by the provider
 *   page, per_page, total_pages, has_next, has_prev
 *   stats    array   outcome statistics for the records actually analysed
 *   provider string  provider name
 *   country  ?string ISO alpha-2 country the records belong to
 *   stale    bool    true when cached data was served instead of a live call
 */
interface LegalDataProvider
{
    /** Human-readable provider name (e.g. "CourtListener"). */
    public function providerName(): string;

    /** ISO-3166-1 alpha-2 codes this provider can actually serve. */
    public function supportedCountries(): array;

    /** Whether valid credentials / configuration exist. */
    public function isConfigured(): bool;

    /**
     * Category definitions this provider can serve.
     *
     * @return array<string, array{label: string, description: string, icon: string}>
     */
    public function categories(): array;

    /**
     * Searchable case types for one category (empty string = all categories).
     *
     * @return list<array{value: string, label: string}>
     */
    public function caseTypes(?string $category): array;

    /**
     * @param  array{q?: string, category?: string, case_type?: string, court?: string, country?: string, date_from?: string, date_to?: string, page?: int, per_page?: int}  $filters
     * @return array<string, mixed> shape documented above
     */
    public function search(array $filters): array;

    /**
     * Full details for one record, by provider id.
     *
     * @return array<string, mixed>|null
     */
    public function getCase(string $id): ?array;

    /**
     * Verified number of retrievable records for one category in a date window.
     * Returns null when the provider cannot answer (unconfigured, throttled, failure).
     */
    public function countCategory(string $category, ?string $dateFrom = null, ?string $dateTo = null): ?int;

    /**
     * Court directory for combobox filters.
     *
     * @return list<array{id: string, name: string, jurisdiction: ?string, location: ?string}>
     */
    public function courts(): array;
}

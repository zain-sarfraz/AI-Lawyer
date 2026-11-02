<?php

namespace App\Domain\CaseLaw\Contracts;

/**
 * Normalized case shape (matches case_law_records upsert payload):
 * external_id, source, title, citation?, decision_date?, source_url?,
 * full_text?, court_name? (resolved to court_id by importer), jurisdiction_code?
 */
interface CaseSourceAdapter
{
    /** @return iterable<int, array<string, mixed>> */
    public function search(array $params): iterable;

    /** @return array<string, mixed> */
    public function fetch(string $externalId): array;
}

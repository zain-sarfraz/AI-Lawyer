<?php

namespace App\Domain\CaseLaw\Adapters;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use RuntimeException;

/** Stub — wire to an official SCP public source when available. */
class SupremeCourtPakistanAdapter implements CaseSourceAdapter
{
    public function search(array $params): iterable
    {
        // TODO: implement official public search when an accessible API/HTML source is confirmed.
        return [];
    }

    public function fetch(string $externalId): array
    {
        throw new RuntimeException("SupremeCourtPakistanAdapter::fetch stub — {$externalId}");
    }
}

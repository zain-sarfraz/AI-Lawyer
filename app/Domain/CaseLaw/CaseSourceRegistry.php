<?php

namespace App\Domain\CaseLaw;

use App\Domain\CaseLaw\Adapters\CourtListenerAdapter;
use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use InvalidArgumentException;

class CaseSourceRegistry
{
    public function forCode(string $jurisdictionCode): CaseSourceAdapter
    {
        $code = strtoupper($jurisdictionCode);
        $map = (array) config('case_law.adapters', []);
        $class = $map[$code] ?? null;

        if (! $class && str_starts_with($code, 'US-')) {
            $class = CourtListenerAdapter::class;
        }

        if (! $class) {
            throw new InvalidArgumentException("No case-law adapter registered for {$jurisdictionCode}");
        }

        return app($class);
    }
}

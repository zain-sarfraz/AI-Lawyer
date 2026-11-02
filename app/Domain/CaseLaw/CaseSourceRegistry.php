<?php

namespace App\Domain\CaseLaw;

use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
use InvalidArgumentException;

class CaseSourceRegistry
{
    public function forCode(string $jurisdictionCode): CaseSourceAdapter
    {
        $map = (array) config('case_law.adapters', []);
        $class = $map[$jurisdictionCode] ?? null;
        if (! $class) {
            throw new InvalidArgumentException("No case-law adapter registered for {$jurisdictionCode}");
        }

        return app($class);
    }
}

<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Scopes\FirmScope;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

class FirmAwareUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null): Builder
    {
        $query = parent::newModelQuery($model);

        return $query->withoutGlobalScope(FirmScope::class);
    }
}

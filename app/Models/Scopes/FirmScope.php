<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Domain\Firm\FirmContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FirmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $firmId = app(FirmContext::class)->id();

        if ($firmId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.firm_id', $firmId);
    }
}

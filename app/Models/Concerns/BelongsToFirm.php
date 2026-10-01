<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Firm\FirmContext;
use App\Models\Firm;
use App\Models\Scopes\FirmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToFirm
{
    public static function bootBelongsToFirm(): void
    {
        static::addGlobalScope(new FirmScope);

        static::creating(function (Model $model): void {
            if (! empty($model->getAttribute('firm_id'))) {
                return;
            }

            $firmId = app(FirmContext::class)->id();
            if ($firmId === null) {
                throw new RuntimeException('A firm context is required to create '.static::class.'.');
            }

            $model->setAttribute('firm_id', $firmId);
        });
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}

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
            $firmId = app(FirmContext::class)->id();
            $existing = $model->getAttribute('firm_id');

            if ($firmId === null) {
                // Platform Superadmin users intentionally have no firm.
                if ($model instanceof \App\Models\User && ($existing === null || $existing === '')) {
                    $model->setAttribute('firm_id', null);

                    return;
                }

                // Individual lawyers intentionally have no firm.
                if ($model instanceof \App\Models\Lawyer && ($existing === null || $existing === '')) {
                    $model->setAttribute('firm_id', null);

                    return;
                }

                // Individual accounts intentionally have no firm.
                $authUser = auth()->user();
                if ($authUser && ($authUser->account_type === 'individual' || $authUser->role === 'individual_lawyer') && $authUser->firm_id === null) {
                    $model->setAttribute('firm_id', null);

                    return;
                }

                if ($existing === null || $existing === '') {
                    throw new RuntimeException('A firm context is required to create '.static::class.'.');
                }

                return;
            }

            if ($existing !== null && $existing !== '' && (int) $existing !== (int) $firmId) {
                throw new RuntimeException('Cannot create '.static::class.' for a different firm.');
            }

            $model->setAttribute('firm_id', $firmId);
        });

        static::updating(function (Model $model): void {
            $contextId = app(FirmContext::class)->id();
            $rawOriginal = $model->getOriginal('firm_id');
            $original = $rawOriginal !== null ? (int) $rawOriginal : null;

            if ($contextId !== null && $original !== null && $original !== (int) $contextId) {
                throw new RuntimeException('Cannot update a '.static::class.' that belongs to another firm.');
            }

            if ($original !== null && $model->isDirty('firm_id') && (int) $model->getAttribute('firm_id') !== $original) {
                throw new RuntimeException('firm_id cannot be reassigned.');
            }
        });
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Firm\FirmContext;
use App\Models\Client;
use App\Models\Lawyer;
use Illuminate\Database\Eloquent\Builder;

trait RestrictsToAssignedLawyer
{
    public static function bootRestrictsToAssignedLawyer(): void
    {
        static::addGlobalScope('assigned_lawyer', function (Builder $builder): void {
            $lawyerId = app(FirmContext::class)->restrictedLawyerId();
            if ($lawyerId === null) {
                return;
            }

            $model = $builder->getModel();
            $table = $model->getTable();

            if ($model instanceof Lawyer) {
                $builder->where($table.'.id', $lawyerId);

                return;
            }

            if ($model instanceof Client) {
                $builder->where(function (Builder $query) use ($lawyerId): void {
                    $query->whereHas('appointments', fn (Builder $appointments) => $appointments->where('lawyer_id', $lawyerId))
                        ->orWhereHas('leads', fn (Builder $leads) => $leads->where('lawyer_id', $lawyerId));
                });

                return;
            }

            $builder->where($table.'.lawyer_id', $lawyerId);
        });
    }
}

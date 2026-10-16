<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\Concerns\BelongsToFirm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    use BelongsToFirm;

    protected $fillable = [
        'firm_id', 'name', 'slug', 'duration_minutes', 'fee', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'fee' => 'decimal:2',
            'status' => EntityStatus::class,
        ];
    }

    public function lawyerFees(): HasMany
    {
        return $this->hasMany(LawyerAppointmentType::class);
    }

    public function feeFor(Lawyer $lawyer): string
    {
        $override = $this->lawyerFees()->where('lawyer_id', $lawyer->id)->value('fee');

        return (string) ($override ?? $this->fee);
    }

    public function scopeActive($query)
    {
        return $query->where('status', EntityStatus::Active);
    }
}

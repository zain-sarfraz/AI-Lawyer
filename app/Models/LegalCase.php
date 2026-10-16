<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\RestrictsToAssignedLawyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    use BelongsToFirm, RestrictsToAssignedLawyer;

    protected $table = 'legal_cases';

    protected $fillable = [
        'firm_id', 'client_id', 'lawyer_id', 'case_number', 'title',
        'status', 'description', 'opened_on',
    ];

    protected function casts(): array
    {
        return [
            'opened_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LegalCase $case): void {
            if (filled($case->case_number)) {
                return;
            }

            $firmId = (int) ($case->firm_id ?: 0);
            $seq = static::query()->withoutGlobalScopes()->where('firm_id', $firmId)->count() + 1;
            $case->case_number = 'CASE-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CrmDocument::class, 'legal_case_id');
    }
}

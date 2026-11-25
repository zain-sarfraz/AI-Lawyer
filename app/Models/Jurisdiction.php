<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurisdiction extends Model
{
    protected $fillable = [
        'code', 'country_code', 'name', 'is_active',
        'last_case_fetch_at', 'last_case_fetch_status', 'last_case_fetch_message',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_case_fetch_at' => 'datetime',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function caseLawRecords(): HasMany
    {
        return $this->hasMany(CaseLawRecord::class);
    }

    public function lawyers(): BelongsToMany
    {
        return $this->belongsToMany(Lawyer::class, 'lawyer_jurisdiction')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, list<array{id:int,code:string,name:string}>> */
    public static function activeGroupedByCountry(): array
    {
        $grouped = [];

        static::query()
            ->active()
            ->orderBy('country_code')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'country_code'])
            ->each(function (self $j) use (&$grouped): void {
                $grouped[$j->country_code][] = [
                    'id' => (int) $j->id,
                    'code' => $j->code,
                    'name' => $j->name,
                ];
            });

        return $grouped;
    }
}

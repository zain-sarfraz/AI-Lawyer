<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = ['firm_id', 'name', 'description', 'status', 'sort_order'];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}

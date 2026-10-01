<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallbackRequest extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = [
        'firm_id', 'name', 'phone', 'email', 'reason', 'lead_id', 'call_id', 'status',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }
}

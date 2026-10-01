<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $table = 'notifications';

    protected $fillable = [
        'firm_id', 'type', 'channel', 'recipient', 'related_type', 'related_id',
        'status', 'provider_id', 'error_message',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}

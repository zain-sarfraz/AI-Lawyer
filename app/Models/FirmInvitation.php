<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToFirm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmInvitation extends Model
{
    use BelongsToFirm;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'firm_id',
        'invited_by',
        'name',
        'email',
        'phone',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_user_id',
        'email_dispatched_at',
        'email_error',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'email_dispatched_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by')->withoutGlobalScopes();
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id')->withoutGlobalScopes();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && $this->expires_at->isPast()) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->save();
        }
    }
}

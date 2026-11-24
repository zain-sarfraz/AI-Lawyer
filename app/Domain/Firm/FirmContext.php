<?php

declare(strict_types=1);

namespace App\Domain\Firm;

use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\User;

/**
 * Explicit firm context for webhooks, queue jobs, and seeders.
 * Authenticated web requests resolve the firm from the signed-in user
 * when nothing has been set explicitly.
 */
class FirmContext
{
    private ?int $firmId = null;

    private ?int $cachedUserId = null;

    private ?int $restrictedLawyerId = null;

    public function set(?int $firmId): void
    {
        $this->firmId = $firmId;
    }

    public function forget(): void
    {
        $this->firmId = null;
        $this->cachedUserId = null;
        $this->restrictedLawyerId = null;
    }

    /**
     * Lawyer id when the signed-in user is a firm Lawyer.
     * Null means the ownership restriction does not apply.
     */
    public function restrictedLawyerId(): ?int
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            $this->cachedUserId = null;

            return null;
        }

        if ($this->cachedUserId !== $user->id) {
            $this->cachedUserId = $user->id;
            // Firm admins see firm-wide data; individual/firm lawyers stay scoped to their seat.
            $restricted = $user->hasRole('Lawyer') && ! $user->isFirmAdmin();
            $this->restrictedLawyerId = $restricted
                ? (int) Lawyer::withoutGlobalScopes()->where('user_id', $user->id)->value('id')
                : null;
        }

        return $this->restrictedLawyerId;
    }

    public function id(): ?int
    {
        if ($this->firmId !== null) {
            return $this->firmId;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return null;
        }

        if ($user->firm_id !== null) {
            return $user->firm_id;
        }

        if ($user->isIndividual() || $user->role === 'individual_lawyer') {
            $firmId = Firm::withoutGlobalScopes()->where('owner_id', $user->id)->value('id');
            if ($firmId) {
                return (int) $firmId;
            }
        }

        return null;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(int $firmId, callable $callback): mixed
    {
        $previous = $this->firmId;
        $this->firmId = $firmId;

        try {
            return $callback();
        } finally {
            $this->firmId = $previous;
        }
    }
}

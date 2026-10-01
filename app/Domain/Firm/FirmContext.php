<?php

declare(strict_types=1);

namespace App\Domain\Firm;

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
     * Lawyer id when the signed-in user is a Lawyer and not Owner/Admin.
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
            $restricted = $user->hasRole('Lawyer') && ! $user->hasAnyRole(['Owner', 'Admin']);
            $this->restrictedLawyerId = $restricted
                ? (int) Lawyer::query()->withoutGlobalScope('assigned_lawyer')->where('user_id', $user->id)->value('id')
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

        return $user instanceof User ? $user->firm_id : null;
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

<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToFirm;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToFirm, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'firm_id',
        'account_type',
        'role',
        'name',
        'email',
        'password',
        'status',
        'stripe_customer_id',
        'onboarding',
        'pending_plan',
        'pending_interval',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'onboarding' => 'array',
        ];
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function lawyer(): HasOne
    {
        return $this->hasOne(Lawyer::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    public function isAdmin(): bool
    {
        return $this->isFirmAdmin();
    }

    public function isFirmAdmin(): bool
    {
        return $this->hasRole('FirmAdmin') || $this->role === 'firm_admin';
    }

    public function isIndividual(): bool
    {
        return $this->account_type === 'individual' || $this->role === 'individual_lawyer';
    }

    public function isFirm(): bool
    {
        return $this->account_type === 'firm' || $this->isFirmAdmin();
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->subscription && $this->subscription->isActive()) {
            return true;
        }

        if ($this->firm && $this->firm->subscription && $this->firm->subscription->isActive()) {
            return true;
        }

        return $this->status === UserStatus::Active && ! empty($this->stripe_customer_id);
    }

    public function isOnboardingDismissed(): bool
    {
        return (bool) ($this->onboarding['dismissed'] ?? false);
    }

    public function isStepCompleted(string $step): bool
    {
        $completed = $this->onboarding['completed_steps'] ?? [];

        return in_array($step, $completed, true);
    }

    public function markStepCompleted(string $step): void
    {
        $onboarding = $this->onboarding ?? ['dismissed' => false, 'completed_steps' => []];
        $completed = $onboarding['completed_steps'] ?? [];
        if (! in_array($step, $completed, true)) {
            $completed[] = $step;
        }
        $onboarding['completed_steps'] = array_values($completed);
        $this->onboarding = $onboarding;
        $this->save();
    }

    public function dismissOnboarding(): void
    {
        $onboarding = $this->onboarding ?? ['completed_steps' => []];
        $onboarding['dismissed'] = true;
        $this->onboarding = $onboarding;
        $this->save();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Superadmin');
    }

    public function isPlatformUser(): bool
    {
        return $this->firm_id === null && $this->isSuperAdmin();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function homeRoute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'superadmin.dashboard';
        }

        if ($this->isFirmAdmin() && $this->firm?->isFirmAccount()) {
            return 'firm.dashboard';
        }

        return 'dashboard';
    }

    /**
     * Emails are unique inside a firm (or among platform users when firm_id is null),
     * but the same email may exist across different firms.
     */
    public static function emailTakenInFirm(string $email, ?int $firmId, ?int $ignoreUserId = null): bool
    {
        $query = static::query()
            ->withoutGlobalScopes()
            ->where('email', strtolower(trim($email)));

        if ($firmId === null) {
            $query->whereNull('firm_id');
        } else {
            $query->where('firm_id', $firmId);
        }

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}

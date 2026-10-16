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
        'name',
        'email',
        'password',
        'status',
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
        ];
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
        return $this->hasAnyRole(['Owner', 'Admin']);
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
        return $this->isSuperAdmin() ? 'superadmin.dashboard' : 'dashboard';
    }
}

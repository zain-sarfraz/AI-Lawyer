<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Domain\Lawyers\LawyerService;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FirmUserService
{
    public function __construct(private readonly LawyerService $lawyers) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string, phone?: ?string, title?: ?string}  $data
     */
    public function create(array $data): User
    {
        $email = strtolower(trim($data['email']));
        $role = $data['role'];

        if (! in_array($role, ['Lawyer', 'Staff'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Role must be Lawyer or Staff.',
            ]);
        }

        if (User::query()->withoutGlobalScopes()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }

        return DB::transaction(function () use ($data, $email, $role) {
            if ($role === 'Lawyer') {
                $lawyer = $this->lawyers->create(
                    [
                        'title' => $data['title'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'status' => 'active',
                        'specialties' => [],
                    ],
                    [
                        'name' => $data['name'],
                        'email' => $email,
                        'password' => $data['password'],
                    ],
                );

                return $lawyer->user->fresh(['roles', 'lawyer']);
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'],
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles(['Staff']);

            return $user->fresh(['roles', 'lawyer']);
        });
    }

    public function setStatus(User $user, string $status): User
    {
        if (! in_array($status, ['active', 'inactive', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status.',
            ]);
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot change status for Owner/Admin accounts here.',
            ]);
        }

        $user->status = UserStatus::from($status);
        $user->save();

        if ($user->lawyer) {
            $user->lawyer->update([
                'status' => $status === 'active' ? 'active' : 'inactive',
            ]);
        }

        return $user->fresh(['roles', 'lawyer']);
    }

    /**
     * @param  array{name: string, email: string, role: string, password?: ?string, phone?: ?string, title?: ?string, status?: ?string}  $data
     */
    public function update(User $user, array $data): User
    {
        $email = strtolower(trim($data['email']));
        $role = $data['role'];

        if (! in_array($role, ['Lawyer', 'Staff'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Role must be Lawyer or Staff.',
            ]);
        }

        if (User::query()->withoutGlobalScopes()->where('email', $email)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role' => 'Cannot modify Owner/Admin accounts here.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $email, $role) {
            $user->name = $data['name'];
            $user->email = $email;
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            if (! empty($data['status'])) {
                $user->status = UserStatus::from($data['status']);
            }
            $user->save();
            $user->syncRoles([$role]);

            if ($role === 'Lawyer') {
                $lawyer = $user->lawyer;
                if ($lawyer) {
                    $lawyer->update([
                        'title' => $data['title'] ?? $lawyer->title,
                        'phone' => $data['phone'] ?? $lawyer->phone,
                        'status' => ($user->status?->value === 'active') ? 'active' : 'inactive',
                    ]);
                } else {
                    $this->lawyers->create(
                        [
                            'title' => $data['title'] ?? null,
                            'phone' => $data['phone'] ?? null,
                            'status' => ($user->status?->value === 'active') ? 'active' : 'inactive',
                            'specialties' => [],
                            'user_id' => $user->id,
                        ]
                    );
                }
            } elseif ($role === 'Staff' && $user->lawyer) {
                $user->lawyer->services()->detach();
                $user->lawyer->delete();
            }

            return $user->fresh(['roles', 'lawyer']);
        });
    }

    public function delete(User $user): void
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Cannot delete Owner or Admin accounts.',
            ]);
        }

        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own logged-in account.',
            ]);
        }

        DB::transaction(function () use ($user) {
            if ($user->lawyer) {
                $this->lawyers->delete($user->lawyer);
            } else {
                $user->delete();
            }
        });
    }
}

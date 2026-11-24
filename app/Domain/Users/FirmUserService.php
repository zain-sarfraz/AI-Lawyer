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
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role?: string,
     *     phone?: ?string,
     *     country?: ?string,
     *     title?: ?string,
     *     jurisdiction_ids?: list<int>
     * }  $data
     */
    public function create(array $data): User
    {
        $email = strtolower(trim($data['email']));
        $firmId = app(\App\Domain\Firm\FirmContext::class)->id();

        if (User::emailTakenInFirm($email, $firmId)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used in this firm.',
            ]);
        }

        return DB::transaction(function () use ($data, $email) {
            $lawyer = $this->lawyers->create(
                [
                    'title' => $data['title'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'] ?? null,
                    'status' => 'active',
                    'specialties' => [],
                ],
                [
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $data['password'],
                ],
                [],
                $data['jurisdiction_ids'] ?? [],
            );

            return $lawyer->user->fresh(['roles', 'lawyer.jurisdictions']);
        });
    }

    public function setStatus(User $user, string $status): User
    {
        if (! in_array($status, ['active', 'inactive', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status.',
            ]);
        }

        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot change status for Superadmin accounts here.',
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
     * @param  array{
     *     name: string,
     *     email: string,
     *     role?: string,
     *     password?: ?string,
     *     phone?: ?string,
     *     country?: ?string,
     *     title?: ?string,
     *     status?: ?string,
     *     jurisdiction_ids?: list<int>
     * }  $data
     */
    public function update(User $user, array $data): User
    {
        $email = strtolower(trim($data['email']));

        if (User::emailTakenInFirm($email, $user->firm_id !== null ? (int) $user->firm_id : null, (int) $user->id)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used in this firm.',
            ]);
        }

        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role' => 'Cannot modify Superadmin accounts here.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $email) {
            $user->name = $data['name'];
            $user->email = $email;
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            if (! empty($data['status'])) {
                $user->status = UserStatus::from($data['status']);
            }
            $user->save();

            // Preserve FirmAdmin owners; members stay Lawyer.
            if (! $user->isFirmAdmin()) {
                $user->syncRoles(['Lawyer']);
            }

            $jurisdictionIds = array_key_exists('jurisdiction_ids', $data)
                ? array_values(array_map('intval', $data['jurisdiction_ids'] ?? []))
                : null;

            $lawyer = $user->lawyer;
            if ($lawyer) {
                $this->lawyers->update(
                    $lawyer,
                    [
                        'title' => $data['title'] ?? $lawyer->title,
                        'phone' => array_key_exists('phone', $data) ? $data['phone'] : $lawyer->phone,
                        'country' => array_key_exists('country', $data) ? $data['country'] : $lawyer->country,
                        'status' => ($user->status?->value === 'active') ? 'active' : 'inactive',
                    ],
                    null,
                    $jurisdictionIds,
                );
            } else {
                $this->lawyers->create(
                    [
                        'title' => $data['title'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'country' => $data['country'] ?? null,
                        'status' => ($user->status?->value === 'active') ? 'active' : 'inactive',
                        'specialties' => [],
                        'user_id' => $user->id,
                    ],
                    null,
                    [],
                    $jurisdictionIds ?? [],
                );
            }

            return $user->fresh(['roles', 'lawyer.jurisdictions']);
        });
    }

    public function delete(User $user): void
    {
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Cannot delete Superadmin accounts.',
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

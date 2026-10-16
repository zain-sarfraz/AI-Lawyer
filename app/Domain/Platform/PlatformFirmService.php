<?php

declare(strict_types=1);

namespace App\Domain\Platform;

use App\Domain\Firm\FirmContext;
use App\Domain\Scheduling\DefaultAppointmentTypes;
use App\Enums\UserStatus;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformFirmService
{
    /**
     * Create an isolated firm workspace (admin is added separately).
     *
     * @param  array{firm_name: string, email?: ?string, phone?: ?string, timezone?: ?string, currency?: ?string}  $data
     */
    public function createFirm(array $data): Firm
    {
        return DB::transaction(function () use ($data) {
            $firm = Firm::query()->create([
                'firm_name' => $data['firm_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'timezone' => $data['timezone'] ?? Firm::DEFAULT_TIMEZONE,
                'currency' => $data['currency'] ?? 'USD',
                'receptionist_name' => 'Virtual Receptionist',
                'status' => 'active',
            ]);

            DefaultAppointmentTypes::seedFirm((int) $firm->id, (string) $firm->currency);

            return $firm->fresh();
        });
    }

    /**
     * Add an Owner/Admin login to an existing firm workspace.
     *
     * @param  array{admin_name: string, admin_email: string, admin_password: string, admin_role?: string}  $data
     */
    public function addAdminToFirm(Firm $firm, array $data): User
    {
        $adminEmail = strtolower(trim($data['admin_email']));
        $role = in_array($data['admin_role'] ?? 'Admin', ['Owner', 'Admin'], true)
            ? ($data['admin_role'] ?? 'Admin')
            : 'Admin';

        if (User::query()->withoutGlobalScopes()->where('email', $adminEmail)->exists()) {
            throw ValidationException::withMessages([
                'admin_email' => 'This email is already registered.',
            ]);
        }

        return app(FirmContext::class)->run((int) $firm->id, function () use ($firm, $data, $adminEmail, $role) {
            $user = User::query()->create([
                'firm_id' => $firm->id,
                'name' => $data['admin_name'],
                'email' => $adminEmail,
                'password' => Hash::make($data['admin_password']),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$role]);

            return $user;
        });
    }

    /**
     * @param  array{firm_name: string, email?: ?string, phone?: ?string, timezone?: ?string, currency?: ?string, status?: string}  $data
     */
    public function updateFirm(Firm $firm, array $data): Firm
    {
        if (isset($data['status']) && ! in_array($data['status'], ['active', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid firm status.',
            ]);
        }

        $firm->fill([
            'firm_name' => $data['firm_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'] ?? $firm->timezone ?? Firm::DEFAULT_TIMEZONE,
            'currency' => $data['currency'] ?? $firm->currency ?? 'USD',
        ]);

        if (isset($data['status'])) {
            $firm->status = $data['status'];
        }

        $firm->save();

        return $firm->fresh();
    }

    /**
     * @param  array{admin_name: string, admin_email: string, firm_id: int, admin_role?: string, status?: string, admin_password?: ?string}  $data
     */
    public function updateAdmin(User $admin, array $data): User
    {
        if ($admin->firm_id === null || $admin->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'admin_email' => 'Platform Superadmin accounts cannot be edited here.',
            ]);
        }

        if (! $admin->hasAnyRole(['Owner', 'Admin'])) {
            throw ValidationException::withMessages([
                'admin_role' => 'Only firm Owner/Admin users can be edited here.',
            ]);
        }

        $adminEmail = strtolower(trim($data['admin_email']));
        $role = in_array($data['admin_role'] ?? 'Admin', ['Owner', 'Admin'], true)
            ? ($data['admin_role'] ?? 'Admin')
            : 'Admin';
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive', 'suspended'], true)
            ? ($data['status'] ?? 'active')
            : 'active';
        $firmId = (int) $data['firm_id'];

        if (! Firm::query()->whereKey($firmId)->exists()) {
            throw ValidationException::withMessages([
                'firm_id' => 'Selected firm was not found.',
            ]);
        }

        $emailTaken = User::query()
            ->withoutGlobalScopes()
            ->where('email', $adminEmail)
            ->where('id', '!=', $admin->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'admin_email' => 'This email is already registered.',
            ]);
        }

        $payload = [
            'name' => $data['admin_name'],
            'email' => $adminEmail,
            'status' => $status,
            'firm_id' => $firmId,
            'updated_at' => now(),
        ];

        if (! empty($data['admin_password'])) {
            $payload['password'] = Hash::make($data['admin_password']);
        }

        // Bypass BelongsToFirm firm_id immutability for platform reassignment.
        DB::table('users')->where('id', $admin->id)->update($payload);

        $admin = User::query()->withoutGlobalScopes()->findOrFail($admin->id);
        $admin->syncRoles([$role]);

        return $admin->fresh(['roles', 'firm']);
    }

    public function setFirmStatus(Firm $firm, string $status): Firm
    {
        if (! in_array($status, ['active', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid firm status.',
            ]);
        }

        $firm->status = $status;
        $firm->save();

        return $firm->fresh();
    }
}

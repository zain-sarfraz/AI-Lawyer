<?php

namespace Database\Seeders;

use App\Domain\Firm\PermissionCatalog;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PermissionCatalog::sync();

        $this->call(JurisdictionSeeder::class);

        // Platform Superadmin only — firm admins are created from the Superadmin portal.
        $userId = DB::table('users')->where('email', 'admin@admin.com')->value('id');

        $payload = [
            'firm_id' => null,
            'name' => 'Superadmin',
            'password' => Hash::make('11223344'),
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ];

        if ($userId) {
            DB::table('users')->where('id', $userId)->update($payload);
        } else {
            $userId = DB::table('users')->insertGetId(array_merge($payload, [
                'email' => 'admin@admin.com',
                'created_at' => now(),
            ]));
        }

        $superadmin = User::query()->withoutGlobalScopes()->findOrFail($userId);
        $superadmin->syncRoles(['Superadmin']);

        // Individual Lawyer demo user
        $lawyerUser = User::query()->withoutGlobalScopes()->firstOrCreate(
            ['email' => 'lawyer@example.com'],
            [
                'name' => 'Individual Lawyer',
                'password' => Hash::make('11223344'),
                'account_type' => 'individual',
                'role' => 'individual_lawyer',
                'status' => UserStatus::Active->value,
                'firm_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $lawyerUser->syncRoles(['Lawyer']);

        // Law Firm demo
        $firm = \App\Models\Firm::query()->firstOrCreate(
            ['slug' => 'apex-legal'],
            [
                'name' => 'Apex Legal Partners',
                'account_type' => 'firm',
                'subscription_status' => 'active',
            ]
        );

        $firmAdmin = User::query()->withoutGlobalScopes()->firstOrCreate(
            ['email' => 'firm@example.com'],
            [
                'name' => 'Firm Administrator',
                'password' => Hash::make('11223344'),
                'account_type' => 'firm',
                'role' => 'firm_admin',
                'status' => UserStatus::Active->value,
                'firm_id' => $firm->id,
                'email_verified_at' => now(),
            ]
        );
        $firmAdmin->syncRoles(['FirmAdmin']);
        $firm->update(['owner_id' => $firmAdmin->id]);
    }
}

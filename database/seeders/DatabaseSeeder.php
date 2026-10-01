<?php

namespace Database\Seeders;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Enums\EntityStatus;
use App\Enums\UserStatus;
use App\Models\FirmSetting;
use App\Models\KnowledgeBaseItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PermissionCatalog::sync();

        $firm = FirmSetting::query()->firstOrCreate(
            ['firm_name' => 'Horizon Legal Group'],
            [
                'legal_name' => 'Horizon Legal Group LLP',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'address' => '100 Main Street, Suite 400',
                'phone' => '+1-555-0100',
                'email' => 'intake@horizonlegal.test',
                'website' => 'https://horizonlegal.test',
                'receptionist_name' => 'Alex',
                'ai_greeting' => 'Thank you for calling Horizon Legal Group. This is Alex, the virtual receptionist. How can I help you today?',
                'ai_tone' => 'professional, calm, concise',
                'ai_language' => 'en',
                'slot_interval_minutes' => 15,
                'default_duration_minutes' => 30,
                'buffer_minutes' => 0,
                'booking_horizon_days' => 60,
                'cancellation_window_hours' => 24,
                'after_hours_behavior' => 'callback',
                'recording_enabled' => false,
            ]
        );

        app(FirmContext::class)->set($firm->id);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@lawfirm.test'],
            [
                'firm_id' => $firm->id,
                'name' => 'Firm Admin',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['Owner']);

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@lawfirm.test'],
            [
                'firm_id' => $firm->id,
                'name' => 'Front Desk',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles(['Staff']);

        KnowledgeBaseItem::query()->firstOrCreate(
            ['title' => 'Office hours'],
            [
                'type' => 'faq',
                'content' => 'Our office is open Monday to Friday, 9 AM to 5 PM Eastern Time.',
                'status' => EntityStatus::Active,
                'ai_visible' => true,
            ]
        );

        KnowledgeBaseItem::query()->firstOrCreate(
            ['title' => 'We do not provide legal advice by phone AI'],
            [
                'type' => 'policy',
                'content' => 'The virtual receptionist can share approved firm information and book consultations. It cannot provide legal advice.',
                'status' => EntityStatus::Active,
                'ai_visible' => true,
            ]
        );

        $this->call(DemoDataSeeder::class);
    }
}

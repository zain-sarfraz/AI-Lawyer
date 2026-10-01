<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\UserStatus;
use App\Models\FirmSetting;
use App\Models\Holiday;
use App\Models\KnowledgeBaseItem;
use App\Models\Lawyer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'firm.settings.manage' => 'Manage firm settings',
            'users.manage' => 'Manage users',
            'lawyers.manage' => 'Manage lawyers',
            'services.manage' => 'Manage services',
            'schedules.manage' => 'Manage schedules',
            'leads.view' => 'View leads',
            'leads.manage' => 'Manage leads',
            'clients.view' => 'View clients',
            'clients.manage' => 'Manage clients',
            'appointments.view' => 'View appointments',
            'appointments.manage' => 'Manage appointments',
            'calls.view' => 'View calls',
            'calls.manage' => 'Manage calls',
            'ai.settings.manage' => 'Manage AI settings',
            'integrations.manage' => 'Manage integrations',
            'audit.view' => 'View audit logs',
            'reports.view' => 'View reports',
            'knowledge.manage' => 'Manage knowledge base',
        ];

        foreach ($permissions as $name => $label) {
            Permission::query()->firstOrCreate(['name' => $name], ['label' => $label]);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $lawyerRole = Role::query()->firstOrCreate(['name' => 'lawyer'], ['label' => 'Lawyer']);
        $staff = Role::query()->firstOrCreate(['name' => 'staff'], ['label' => 'Receptionist / Staff']);

        $admin->permissions()->sync(Permission::query()->pluck('id'));
        $lawyerRole->permissions()->sync(
            Permission::query()->whereIn('name', [
                'leads.view', 'clients.view', 'appointments.view', 'appointments.manage',
                'calls.view', 'schedules.manage', 'reports.view',
            ])->pluck('id')
        );
        $staff->permissions()->sync(
            Permission::query()->whereIn('name', [
                'leads.view', 'leads.manage', 'clients.view', 'clients.manage',
                'appointments.view', 'appointments.manage', 'calls.view', 'calls.manage',
                'reports.view',
            ])->pluck('id')
        );

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@lawfirm.test'],
            [
                'name' => 'Firm Admin',
                'password' => Hash::make('password'),
                'role_id' => $admin->id,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@lawfirm.test'],
            [
                'name' => 'Front Desk',
                'password' => Hash::make('password'),
                'role_id' => $staff->id,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        FirmSetting::query()->firstOrCreate([], [
            'firm_name' => 'Horizon Legal Group',
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
        ]);

        $category = ServiceCategory::query()->firstOrCreate(
            ['name' => 'Consultations'],
            ['description' => 'Initial legal consultations', 'status' => EntityStatus::Active, 'sort_order' => 1]
        );

        $consultation = Service::query()->firstOrCreate(
            ['name' => 'Initial Consultation'],
            [
                'category_id' => $category->id,
                'description' => 'A 30-minute introductory consultation with a lawyer.',
                'price' => 150.00,
                'currency' => 'USD',
                'duration_minutes' => 30,
                'booking_enabled' => true,
                'ai_enabled' => true,
                'status' => EntityStatus::Active,
            ]
        );

        $family = Service::query()->firstOrCreate(
            ['name' => 'Family Law Consultation'],
            [
                'category_id' => $category->id,
                'description' => 'Consultation focused on family law matters.',
                'price' => 200.00,
                'currency' => 'USD',
                'duration_minutes' => 45,
                'booking_enabled' => true,
                'ai_enabled' => true,
                'status' => EntityStatus::Active,
            ]
        );

        $lawyerUser = User::query()->updateOrCreate(
            ['email' => 'lawyer@lawfirm.test'],
            [
                'name' => 'Jordan Lee',
                'password' => Hash::make('password'),
                'role_id' => $lawyerRole->id,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $lawyer = Lawyer::query()->updateOrCreate(
            ['user_id' => $lawyerUser->id],
            [
                'title' => 'Partner',
                'bio' => 'Experienced counsel focusing on consultations and family law.',
                'specialties' => ['family', 'general consultation'],
                'timezone' => 'America/New_York',
                'status' => EntityStatus::Active,
            ]
        );
        $lawyer->services()->sync([$consultation->id, $family->id]);

        // Mon-Fri 09:00-17:00 with lunch break 12:00-13:00
        for ($day = 1; $day <= 5; $day++) {
            WorkingHour::query()->firstOrCreate(
                ['lawyer_id' => $lawyer->id, 'day_of_week' => $day, 'start_time' => '09:00:00'],
                ['end_time' => '17:00:00', 'status' => 'active']
            );
            \App\Models\ScheduleBreak::query()->firstOrCreate(
                ['lawyer_id' => $lawyer->id, 'day_of_week' => $day, 'start_time' => '12:00:00'],
                ['end_time' => '13:00:00']
            );
        }

        // Firm default hours as fallback
        for ($day = 1; $day <= 5; $day++) {
            WorkingHour::query()->firstOrCreate(
                ['lawyer_id' => null, 'day_of_week' => $day, 'start_time' => '09:00:00'],
                ['end_time' => '17:00:00', 'status' => 'active']
            );
        }

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

        unset($adminUser); // silence unused in some analyzers
    }
}

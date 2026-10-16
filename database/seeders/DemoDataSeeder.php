<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\CallDirection;
use App\Enums\EntityStatus;
use App\Enums\LeadStatus;
use App\Enums\UserStatus;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Client;
use App\Models\Lawyer;
use App\Models\Lead;
use App\Models\ScheduleBreak;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Client::query()->count() >= 40) {
            return;
        }

        $areas = [
            ['Family Law', 'Jordan Lee', 'jordan.lee@lawfirm.test', 'Partner', 200, 45],
            ['Criminal Defense', 'Amina Shah', 'amina.shah@lawfirm.test', 'Senior Associate', 250, 60],
            ['Corporate', 'Noah Patel', 'noah.patel@lawfirm.test', 'Partner', 350, 60],
            ['Real Estate', 'Sofia Alvarez', 'sofia.alvarez@lawfirm.test', 'Associate', 180, 30],
            ['Immigration', 'Hassan Rahman', 'hassan.rahman@lawfirm.test', 'Counsel', 220, 45],
            ['Estate Planning', 'Claire Nguyen', 'claire.nguyen@lawfirm.test', 'Partner', 275, 45],
        ];

        $lawyers = [];
        $services = [];

        foreach ($areas as $index => [$area, $name, $email, $title, $fee, $minutes]) {
            $category = ServiceCategory::query()->firstOrCreate(
                ['name' => $area],
                [
                    'description' => $area.' matters handled by Horizon Legal Group.',
                    'status' => EntityStatus::Active,
                    'sort_order' => $index + 1,
                ]
            );

            $service = Service::query()->firstOrCreate(
                ['name' => $area.' Consultation'],
                [
                    'category_id' => $category->id,
                    'description' => 'Paid consultation for '.$area.'.',
                    'price' => $fee,
                    'currency' => 'USD',
                    'duration_minutes' => $minutes,
                    'booking_enabled' => true,
                    'ai_enabled' => true,
                    'status' => EntityStatus::Active,
                ]
            );

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles(['Lawyer']);

            $lawyer = Lawyer::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'title' => $title,
                    'bio' => $name.' advises clients on '.$area.'.',
                    'specialties' => [$area],
                    'timezone' => 'America/New_York',
                    'status' => EntityStatus::Active,
                ]
            );
            $lawyer->services()->sync([$service->id]);

            for ($day = 1; $day <= 5; $day++) {
                WorkingHour::query()->firstOrCreate(
                    ['lawyer_id' => $lawyer->id, 'day_of_week' => $day, 'start_time' => '09:00:00'],
                    ['end_time' => '17:00:00', 'status' => 'active']
                );
                ScheduleBreak::query()->firstOrCreate(
                    ['lawyer_id' => $lawyer->id, 'day_of_week' => $day, 'start_time' => '12:00:00'],
                    ['end_time' => '13:00:00']
                );
            }

            $lawyers[] = $lawyer;
            $services[] = $service;
        }

        $statuses = [
            LeadStatus::New,
            LeadStatus::Contacted,
            LeadStatus::Qualified,
            LeadStatus::ConsultationBooked,
            LeadStatus::FollowUp,
        ];

        for ($i = 1; $i <= 40; $i++) {
            $lawyer = $lawyers[$i % count($lawyers)];
            $service = $services[$i % count($services)];
            $client = Client::query()->create([
                'name' => fake()->name(),
                'phone' => fake()->unique()->e164PhoneNumber(),
                'email' => fake()->unique()->safeEmail(),
                'status' => EntityStatus::Active,
                'notes' => 'Demo client '.$i,
            ]);

            $lead = Lead::query()->create([
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'service_id' => $service->id,
                'lawyer_id' => $lawyer->id,
                'status' => $statuses[$i % count($statuses)],
                'source' => $i % 2 === 0 ? 'phone' : 'web',
                'notes' => 'Intake note for '.$service->name,
                'last_contact_at' => now()->subDays($i % 14),
            ]);
            $client->leads()->attach($lead->id);

            if ($i <= 24) {
                $start = now()->addDays($i)->setTime(10, 0);
                Appointment::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'client_id' => $client->id,
                    'lead_id' => $lead->id,
                    'service_id' => $service->id,
                    'start_at' => $start,
                    'end_at' => (clone $start)->addMinutes($service->duration_minutes),
                    'status' => $i % 5 === 0 ? AppointmentStatus::Completed : AppointmentStatus::Pending,
                    'source' => 'demo',
                    'notes' => 'Consultation '.$i,
                ]);
            }

            if ($i <= 18) {
                Call::query()->create([
                    'provider_call_id' => 'CADEMO'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'direction' => CallDirection::Inbound,
                    'caller_phone' => $client->phone,
                    'lawyer_id' => $lawyer->id,
                    'lead_id' => $lead->id,
                    'client_id' => $client->id,
                    'started_at' => now()->subDays($i % 7)->subMinutes(20),
                    'ended_at' => now()->subDays($i % 7)->subMinutes(8),
                    'duration_seconds' => 720,
                    'status' => 'completed',
                    'summary' => 'Caller asked about '.$service->name.' and requested a consultation.',
                    'intent' => 'book_consultation',
                    'outcome' => 'appointment_offered',
                ]);
            }
        }
    }
}

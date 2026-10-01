<?php

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Models\Appointment;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
    }

    public function test_a_user_cannot_see_or_edit_another_firms_lead(): void
    {
        $firmA = Firm::factory()->create(['firm_name' => 'Firm A']);
        $firmB = Firm::factory()->create(['firm_name' => 'Firm B']);

        $userA = User::factory()->create(['firm_id' => $firmA->id, 'email' => 'owner-a@example.com']);
        $userA->assignRole('Admin');

        $leadB = app(FirmContext::class)->run($firmB->id, function () {
            return Lead::query()->create([
                'name' => 'Secret Client B',
                'phone' => '+15550001111',
                'status' => 'new',
                'source' => 'test',
            ]);
        });

        $this->actingAs($userA)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertDontSee('Secret Client B');

        $this->actingAs($userA)
            ->get(route('leads.show', $leadB))
            ->assertNotFound();

        $this->actingAs($userA)
            ->put(route('leads.update', $leadB), [
                'name' => 'Hacked',
                'status' => 'contacted',
            ])
            ->assertNotFound();

        $this->assertFalse($userA->can('view', $leadB));
        $this->assertFalse($userA->can('update', $leadB));
        $this->assertDatabaseHas('leads', [
            'id' => $leadB->id,
            'name' => 'Secret Client B',
        ]);
    }

    public function test_a_lawyer_sees_only_their_own_appointments(): void
    {
        $firm = Firm::factory()->create();
        $lawyerUser = User::factory()->create(['firm_id' => $firm->id, 'email' => 'lawyer-own@example.com', 'name' => 'Own Counsel']);
        $otherUser = User::factory()->create(['firm_id' => $firm->id, 'email' => 'lawyer-other@example.com', 'name' => 'Hidden Counsel']);
        $lawyerUser->assignRole('Lawyer');
        $otherUser->assignRole('Lawyer');

        [$own, $other] = app(FirmContext::class)->run($firm->id, function () use ($lawyerUser, $otherUser) {
            $service = Service::query()->create([
                'name' => 'Consult',
                'price' => 100,
                'currency' => 'USD',
                'duration_minutes' => 30,
                'booking_enabled' => true,
                'ai_enabled' => true,
                'status' => 'active',
            ]);
            $lawyer = Lawyer::query()->create([
                'user_id' => $lawyerUser->id,
                'title' => 'Associate',
                'status' => 'active',
            ]);
            $otherLawyer = Lawyer::query()->create([
                'user_id' => $otherUser->id,
                'title' => 'Partner',
                'status' => 'active',
            ]);
            $start = now()->addDay()->setTime(10, 0);

            $own = Appointment::query()->create([
                'lawyer_id' => $lawyer->id,
                'service_id' => $service->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addMinutes(30),
                'status' => 'scheduled',
                'notes' => 'Own calendar matter',
            ]);
            $otherAppointment = Appointment::query()->create([
                'lawyer_id' => $otherLawyer->id,
                'service_id' => $service->id,
                'start_at' => $start->copy()->addHour(),
                'end_at' => $start->copy()->addHour()->addMinutes(30),
                'status' => 'scheduled',
                'notes' => 'Other lawyer matter',
            ]);

            return [$own, $otherAppointment];
        });

        $this->actingAs($lawyerUser);
        $this->assertSame(1, Appointment::query()->count());
        $this->assertTrue($lawyerUser->can('view', $own));
        $this->assertFalse($lawyerUser->can('view', $other));
        $this->assertFalse($lawyerUser->can('update', $other));

        $this->actingAs($lawyerUser)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertDontSee('Hidden Counsel');

        $this->actingAs($lawyerUser)
            ->post(route('appointments.cancel', $other))
            ->assertNotFound();
    }
}

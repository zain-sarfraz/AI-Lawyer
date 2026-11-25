<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Enums\UserStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
    }

    public function test_superadmin_lawyers_page_hides_view_toggle_and_appointments_has_dedicated_page(): void
    {
        $superadmin = User::factory()->create([
            'firm_id' => null,
            'email' => 'sa-pages@example.com',
            'status' => UserStatus::Active,
        ]);
        $superadmin->assignRole('Superadmin');

        $firm = Firm::factory()->create(['firm_name' => 'Apex Legal']);
        $lawyerUser = User::factory()->create([
            'firm_id' => $firm->id,
            'email' => 'counsel@apex.test',
            'name' => 'Counsel Tariq',
            'status' => UserStatus::Active,
        ]);
        $lawyerUser->assignRole('Lawyer');

        app(FirmContext::class)->run($firm->id, function () use ($lawyerUser) {
            $service = Service::query()->create([
                'name' => 'Family Consultation',
                'price' => 150,
                'currency' => 'USD',
                'duration_minutes' => 30,
                'booking_enabled' => true,
                'ai_enabled' => true,
                'status' => 'active',
            ]);
            $lawyer = Lawyer::query()->create([
                'user_id' => $lawyerUser->id,
                'title' => 'Senior Partner',
                'status' => 'active',
            ]);
            $client = Client::query()->create([
                'name' => 'Bilal Ahmed',
                'phone' => '+923001234567',
                'status' => 'active',
            ]);
            $start = now()->addDay()->setTime(11, 0);
            Appointment::query()->create([
                'lawyer_id' => $lawyer->id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addMinutes(30),
                'status' => 'confirmed',
                'source' => 'phone_ai',
                'fee_amount' => 150,
            ]);
        });

        // 1. Lawyers page must not render the grid/list view toggle
        $this->actingAs($superadmin)
            ->get(route('superadmin.lawyers.index'))
            ->assertOk()
            ->assertDontSee('sa-view-toggle')
            ->assertDontSee('title="Grid View"', false)
            ->assertSee(route('superadmin.appointments.index'));

        // 2. Separate Appointments page renders platform-wide appointments and filters cleanly
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index'))
            ->assertOk()
            ->assertSee('All Appointments')
            ->assertSee('Bilal Ahmed')
            ->assertSee('Counsel Tariq')
            ->assertSee('Apex Legal');

        // 3. Filtering works
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['source' => 'phone_ai', 'status' => 'confirmed']))
            ->assertOk()
            ->assertSee('Bilal Ahmed');

        // 4. Firm user cannot access superadmin appointments
        $this->actingAs($lawyerUser)
            ->get(route('superadmin.appointments.index'))
            ->assertForbidden();
    }

    public function test_login_page_does_not_expose_new_lawyer_credentials_and_superadmin_can_delete_individual_lawyer(): void
    {
        $superadmin = User::factory()->create([
            'firm_id' => null,
            'email' => 'sa-delete@example.com',
            'status' => UserStatus::Active,
        ]);
        $superadmin->assignRole('Superadmin');

        $soloUser = User::factory()->create([
            'firm_id' => null,
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'name' => 'Secret Solo Lawyer',
            'email' => 'secret.solo.lawyer@example.com',
            'status' => UserStatus::Active,
        ]);
        $soloUser->assignRole('Lawyer');

        $soloLawyer = Lawyer::withoutGlobalScopes()->create([
            'firm_id' => null,
            'user_id' => $soloUser->id,
            'title' => 'Individual Lawyer',
            'country' => 'US',
            'status' => 'active',
            'specialties' => [],
        ]);

        // 1. Login page must NOT show newly created lawyer email/credentials
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('secret.solo.lawyer@example.com')
            ->assertDontSee('Secret Solo Lawyer');

        // 2. Deleting an individual lawyer (firm_id = null) must succeed and write audit log with firm_id = null
        $this->actingAs($superadmin)
            ->delete(route('superadmin.lawyers.destroy', $soloLawyer->id))
            ->assertRedirect(route('superadmin.lawyers.index'));

        $this->assertDatabaseMissing('lawyers', ['id' => $soloLawyer->id]);
        $this->assertDatabaseMissing('users', ['id' => $soloUser->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lawyer.deleted',
            'firm_id' => null,
        ]);
    }

    public function test_superadmin_appointments_supports_all_multi_criteria_filters(): void
    {
        $superadmin = User::factory()->create([
            'firm_id' => null,
            'email' => 'sa-filters@example.com',
            'status' => UserStatus::Active,
        ]);
        $superadmin->assignRole('Superadmin');

        $firm = Firm::factory()->create(['firm_name' => 'Apex Legal Filter', 'account_type' => 'firm', 'plan' => 'growth']);
        $firmUser = User::factory()->create(['firm_id' => $firm->id, 'name' => 'Counsel Alpha', 'account_type' => 'firm', 'status' => UserStatus::Active]);
        $firmUser->assignRole('Lawyer');

        $indFirm = Firm::factory()->create(['firm_name' => 'Solo Filter Practice', 'account_type' => 'individual', 'plan' => 'starter']);
        $indUser = User::factory()->create([
            'firm_id' => $indFirm->id,
            'name' => 'Solo Beta',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::Active,
        ]);
        $indUser->assignRole('Lawyer');

        [$firmLawyer, $firmService] = app(FirmContext::class)->run($firm->id, function () use ($firmUser) {
            $lawyer = Lawyer::query()->create(['user_id' => $firmUser->id, 'title' => 'Partner', 'status' => 'active']);
            $service = Service::query()->create(['name' => 'Corporate Merger', 'price' => 500, 'currency' => 'USD', 'duration_minutes' => 45, 'status' => 'active']);
            $client = Client::query()->create(['name' => 'Client One Firm', 'phone' => '+15551110001', 'status' => 'active']);
            Appointment::query()->create([
                'lawyer_id' => $lawyer->id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'start_at' => now()->addDays(5)->setTime(14, 0),
                'end_at' => now()->addDays(5)->setTime(14, 45),
                'status' => 'confirmed',
                'source' => 'phone_ai',
                'fee_amount' => 500,
            ]);

            return [$lawyer, $service];
        });

        [$indLawyer, $indService] = app(FirmContext::class)->run($indFirm->id, function () use ($indUser) {
            $lawyer = Lawyer::query()->create(['user_id' => $indUser->id, 'title' => 'Individual Lawyer', 'status' => 'active']);
            $service = Service::query()->create(['name' => 'Family Dispute', 'price' => 120, 'currency' => 'USD', 'duration_minutes' => 30, 'status' => 'active']);
            $client = Client::query()->create(['name' => 'Client Two Solo', 'phone' => '+15552220002', 'status' => 'active']);
            Appointment::query()->create([
                'lawyer_id' => $lawyer->id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'start_at' => now()->setTime(11, 0),
                'end_at' => now()->setTime(11, 30),
                'status' => 'cancelled',
                'source' => 'staff',
                'fee_amount' => 120,
            ]);

            return [$lawyer, $service];
        });

        // Account Type filter
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['account_type' => 'firm']))
            ->assertOk()
            ->assertSee('Client One Firm')
            ->assertDontSee('Client Two Solo');

        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['account_type' => 'individual']))
            ->assertOk()
            ->assertSee('Client Two Solo')
            ->assertDontSee('Client One Firm');

        // Lawyer filter
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['lawyer_id' => $indLawyer->id]))
            ->assertOk()
            ->assertSee('Client Two Solo')
            ->assertDontSee('Client One Firm');

        // Service filter + service search
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['service_id' => $firmService->id]))
            ->assertOk()
            ->assertSee('Client One Firm')
            ->assertDontSee('Client Two Solo');

        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['search' => 'Family Dispute']))
            ->assertOk()
            ->assertSee('Client Two Solo')
            ->assertDontSee('Client One Firm');

        // Status "active" filter (pending/confirmed only)
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Client One Firm')
            ->assertDontSee('Client Two Solo');

        // Date preset "today" and explicit date_from/date_to range
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['date_preset' => 'today']))
            ->assertOk()
            ->assertSee('Client Two Solo')
            ->assertDontSee('Client One Firm');

        $futureDate = now()->addDays(5)->toDateString();
        $this->actingAs($superadmin)
            ->get(route('superadmin.appointments.index', ['date_from' => $futureDate, 'date_to' => $futureDate, 'sort' => 'highest_fee']))
            ->assertOk()
            ->assertSee('Client One Firm')
            ->assertDontSee('Client Two Solo');
    }
}

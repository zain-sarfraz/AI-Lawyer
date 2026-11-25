<?php

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Http\Controllers\Admin\AppointmentController;
use App\Models\Appointment;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLoginAndSuperadminFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
    }

    public function test_notification_feed_matches_appointments_page_firm_timezone(): void
    {
        $firm = Firm::factory()->create(['timezone' => 'Asia/Karachi']);
        $user = User::factory()->create([
            'firm_id' => $firm->id,
            'email' => 'lawyer.tz@example.test',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('FirmAdmin');

        $appointment = app(FirmContext::class)->run((int) $firm->id, function () use ($user) {
            $lawyer = Lawyer::query()->create([
                'user_id' => $user->id,
                'title' => 'Lawyer',
                'status' => 'active',
            ]);
            $service = Service::query()->create([
                'name' => 'Family Case',
                'price' => 45,
                'currency' => 'USD',
                'duration_minutes' => 30,
                'booking_enabled' => true,
                'ai_enabled' => true,
                'status' => 'active',
            ]);
            $lead = Lead::query()->create([
                'name' => 'Zain',
                'phone' => '03247087907',
                'status' => 'new',
            ]);

            return Appointment::query()->create([
                'lawyer_id' => $lawyer->id,
                'service_id' => $service->id,
                'lead_id' => $lead->id,
                'start_at' => '2026-11-25 10:00:00',
                'end_at' => '2026-11-25 10:30:00',
                'status' => 'pending',
                'source' => 'staff',
            ]);
        });

        $detail = app(FirmContext::class)->run((int) $firm->id, fn () => AppointmentController::detailPayload($appointment->fresh()));

        $feed = $this->actingAs($user)
            ->getJson(route('notifications.appointments.feed'))
            ->assertOk()
            ->json('items.0');

        $this->assertSame('3:00 PM', $detail['time']);
        $this->assertSame('3:00 PM', $feed['time']);
        $this->assertSame('Nov 25, 2026', $feed['date']);
    }

    public function test_login_page_shows_superadmin_and_firm_admin_demo_credentials_only(): void
    {
        $firm = Firm::factory()->create(['account_type' => 'individual']);
        User::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Private Checkout User',
            'email' => 'private.newuser@example.test',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => 'active',
        ]);

        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('admin@admin.com')
            ->assertSee('firmtest@gmail.com')
            ->assertSee('11223344')
            ->assertDontSee('private.newuser@example.test');
    }

    public function test_superadmin_lawyers_account_type_filters_work(): void
    {
        $sa = User::factory()->create(['firm_id' => null, 'status' => 'active', 'email_verified_at' => now()]);
        $sa->assignRole('Superadmin');

        $firm = Firm::factory()->create(['firm_name' => 'Alpha Law Firm', 'account_type' => 'firm']);
        $firmUser = User::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Firm Counsel One',
            'account_type' => 'firm',
            'role' => null,
            'status' => 'active',
        ]);
        Lawyer::withoutGlobalScopes()->create([
            'firm_id' => $firm->id,
            'user_id' => $firmUser->id,
            'title' => 'Partner',
            'status' => 'active',
        ]);

        $indFirm = Firm::factory()->create(['firm_name' => 'Solo Practice', 'account_type' => 'individual']);
        $indUser = User::factory()->create([
            'firm_id' => $indFirm->id,
            'name' => 'Solo Counsel Two',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => 'active',
        ]);
        Lawyer::withoutGlobalScopes()->create([
            'firm_id' => $indFirm->id,
            'user_id' => $indUser->id,
            'title' => 'Individual Lawyer',
            'status' => 'active',
        ]);

        $this->actingAs($sa)
            ->get('/superadmin/lawyers?account_type=firm')
            ->assertOk()
            ->assertSee('Firm Counsel One')
            ->assertDontSee('Solo Counsel Two');

        $this->actingAs($sa)
            ->get('/superadmin/lawyers?account_type=individual')
            ->assertOk()
            ->assertSee('Solo Counsel Two')
            ->assertDontSee('Firm Counsel One');
    }

    public function test_individual_lawyer_labels_are_shown_on_profile_dashboard_and_superadmin_views(): void
    {
        $sa = User::factory()->create(['firm_id' => null, 'status' => 'active', 'email_verified_at' => now()]);
        $sa->assignRole('Superadmin');

        $firm = Firm::factory()->create(['firm_name' => 'Beacon Legal LLP', 'account_type' => 'firm', 'plan' => 'growth']);
        $firmAdmin = User::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Farah Firm',
            'account_type' => 'firm',
            'role' => 'firm_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $firmAdmin->assignRole('FirmAdmin');

        $indFirm = Firm::factory()->create([
            'firm_name' => "Zain Test's Practice",
            'account_type' => 'individual',
            'plan' => 'starter',
        ]);
        $indUser = User::factory()->create([
            'firm_id' => $indFirm->id,
            'name' => 'Zain Test',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $indUser->assignRole('Lawyer');
        $indFirm->update(['owner_id' => $indUser->id]);

        $indLawyer = Lawyer::withoutGlobalScopes()->create([
            'firm_id' => $indFirm->id,
            'user_id' => $indUser->id,
            'title' => 'Lawyer',
            'status' => 'active',
        ]);

        app(FirmContext::class)->run((int) $indFirm->id, function () use ($indLawyer) {
            $lead = Lead::query()->create(['name' => 'Ali Client', 'phone' => '+923001112233', 'status' => 'new']);
            Appointment::query()->create([
                'lawyer_id' => $indLawyer->id,
                'lead_id' => $lead->id,
                'start_at' => now()->addDay(),
                'end_at' => now()->addDay()->addMinutes(30),
                'status' => 'confirmed',
                'source' => 'phone_ai',
            ]);
        });

        // Individual lawyer workspace UI (Profile & Dashboard)
        $this->actingAs($indUser)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Zain Test')
            ->assertSee('Individual Lawyer')
            ->assertSee('Individual practice')
            ->assertDontSee("ZAIN TEST'S PRACTICE");

        $this->actingAs($indUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Individual Lawyer desk');

        // Superadmin Firms list & detail show Individual cleanly
        $this->actingAs($sa)
            ->get(route('superadmin.firms.index'))
            ->assertOk()
            ->assertSee('Starter · Individual Lawyer')
            ->assertSee('Beacon Legal LLP')
            ->assertSee('Growth · Law Firm')
            ->assertDontSee("Zain Test's Practice");

        $this->actingAs($sa)
            ->get(route('superadmin.firms.show', $indFirm))
            ->assertOk()
            ->assertSee('Individual Lawyer')
            ->assertSee('Zain Test');

        // Superadmin Lawyers list shows Individual in Firm column
        $this->actingAs($sa)
            ->get(route('superadmin.lawyers.index', ['account_type' => 'individual']))
            ->assertOk()
            ->assertSee('Zain Test')
            ->assertSee('Individual Lawyer')
            ->assertDontSee("Zain Test's Practice");

        // Superadmin Appointments shows & filters Individual accounts including firm-backed solo practices
        $this->actingAs($sa)
            ->get(route('superadmin.appointments.index', ['firm_id' => 'individual']))
            ->assertOk()
            ->assertSee('Ali Client')
            ->assertSee('Individual Lawyer')
            ->assertDontSee("Zain Test's Practice");
    }
}

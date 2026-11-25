<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Enums\UserStatus;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LawyerProfilePracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
    }

    public function test_individual_lawyer_sees_and_can_update_country_code_and_case_jurisdictions_on_profile(): void
    {
        $pkJur = Jurisdiction::query()->create([
            'code' => 'PK-SC',
            'country_code' => 'PK',
            'name' => 'Supreme Court of Pakistan',
            'is_active' => true,
        ]);
        $usJur = Jurisdiction::query()->create([
            'code' => 'US-FED',
            'country_code' => 'US',
            'name' => 'United States Federal Courts',
            'is_active' => true,
        ]);

        // 1. Provision individual lawyer via checkout activation with preferred country PK
        $user = User::factory()->create([
            'email' => 'solo.pk@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'firm_id' => null,
            'onboarding' => [
                'preferred_country' => 'PK',
                'preferred_phone' => '+923247087907',
                'preferred_jurisdiction_ids' => [$pkJur->id],
            ],
        ]);
        $user->assignRole('Lawyer');

        $sessionId = 'cs_mock_solo_pk_12345';
        Subscription::create([
            'user_id' => $user->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_checkout_session_id' => $sessionId,
            'status' => 'incomplete',
        ]);

        $this->get(route('checkout.success', ['mock' => 1, 'session_id' => $sessionId]))->assertOk();

        $user->refresh();
        $lawyer = Lawyer::withoutGlobalScopes()->where('user_id', $user->id)->first();
        $this->assertNotNull($lawyer);
        $this->assertSame('PK', $lawyer->country);
        $this->assertSame([$pkJur->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());

        // 2. Profile page shows editable country code & case jurisdictions
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Country Code')
            ->assertSee('Supreme Court of Pakistan')
            ->assertDontSee('Managed by your Firm Admin');

        // 3. Individual lawyer updates country code to US and selects US Federal Courts
        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'phone_country' => 'US',
                'phone_local' => '2025550143',
                'jurisdiction_ids' => [$usJur->id],
            ])
            ->assertRedirect(route('profile.edit'));

        $lawyer->refresh();
        $this->assertSame('US', $lawyer->country);
        $this->assertSame('+12025550143', $lawyer->phone);
        $this->assertSame([$usJur->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
    }

    public function test_firm_lawyer_sees_country_and_cases_read_only_and_only_firm_admin_can_change_them(): void
    {
        $pkJur = Jurisdiction::query()->create([
            'code' => 'PK-SC',
            'country_code' => 'PK',
            'name' => 'Supreme Court of Pakistan',
            'is_active' => true,
        ]);
        $gbJur = Jurisdiction::query()->create([
            'code' => 'GB-SC',
            'country_code' => 'GB',
            'name' => 'UK Supreme Court',
            'is_active' => true,
        ]);

        $firm = Firm::factory()->create([
            'firm_name' => 'Lahore Chambers',
            'account_type' => 'firm',
            'plan' => 'growth',
        ]);

        $firmAdmin = User::factory()->create([
            'firm_id' => $firm->id,
            'account_type' => 'firm',
            'role' => 'firm_admin',
            'email' => 'admin@lahorechambers.test',
            'status' => UserStatus::Active,
        ]);
        $firmAdmin->assignRole('FirmAdmin');

        $memberUser = User::factory()->create([
            'firm_id' => $firm->id,
            'account_type' => 'firm',
            'role' => 'lawyer',
            'email' => 'associate@lahorechambers.test',
            'status' => UserStatus::Active,
        ]);
        $memberUser->assignRole('Lawyer');

        $memberLawyer = app(FirmContext::class)->run($firm->id, function () use ($memberUser, $pkJur) {
            $lawyer = Lawyer::query()->create([
                'user_id' => $memberUser->id,
                'title' => 'Associate Counsel',
                'country' => 'PK',
                'phone' => '+923247087907',
                'status' => 'active',
            ]);
            $lawyer->jurisdictions()->sync([$pkJur->id]);

            return $lawyer;
        });

        // 1. Firm lawyer sees their country code and cases on /profile (read-only)
        $this->actingAs($memberUser)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Practice Country & Case Law Access')
            ->assertSee('Pakistan (PK · +92)')
            ->assertSee('Supreme Court of Pakistan')
            ->assertSee('Managed by your Firm Admin');

        // 2. Firm lawyer attempting to change country code or jurisdictions via /profile is ignored
        $this->actingAs($memberUser)
            ->patch(route('profile.update'), [
                'name' => $memberUser->name,
                'email' => $memberUser->email,
                'phone_country' => 'GB',
                'phone_local' => '7911123456',
                'jurisdiction_ids' => [$gbJur->id],
            ])
            ->assertRedirect(route('profile.edit'));

        $memberLawyer->refresh();
        $this->assertSame('PK', $memberLawyer->country);
        $this->assertSame('+923247087907', $memberLawyer->phone);
        $this->assertSame([$pkJur->id], $memberLawyer->jurisdictions()->pluck('jurisdictions.id')->all());

        // 3. Firm Admin sees it on member profile and CAN update country code & jurisdictions
        $this->actingAs($firmAdmin)
            ->get(route('firm.lawyers.show', $memberUser))
            ->assertOk()
            ->assertSee('Pakistan (PK · +92)')
            ->assertSee('PK-SC');

        $this->actingAs($firmAdmin)
            ->put(route('firm.lawyers.update', $memberUser), [
                'name' => $memberUser->name,
                'email' => $memberUser->email,
                'title' => 'Senior Counsel',
                'phone_country' => 'GB',
                'phone_local' => '7911123456',
                'jurisdiction_ids' => [$gbJur->id],
            ])
            ->assertRedirect(route('firm.lawyers.index'));

        $memberLawyer->refresh();
        $this->assertSame('GB', $memberLawyer->country);
        $this->assertSame('+447911123456', $memberLawyer->phone);
        $this->assertSame([$gbJur->id], $memberLawyer->jurisdictions()->pluck('jurisdictions.id')->all());
    }

    public function test_selecting_pakistan_on_checkout_or_profile_automatically_assigns_only_pakistan_cases_and_hides_checkout_jurisdiction_list(): void
    {
        $pkJur = Jurisdiction::query()->create([
            'code' => 'PK-SC',
            'country_code' => 'PK',
            'name' => 'Supreme Court of Pakistan',
            'is_active' => true,
        ]);
        $gbJur = Jurisdiction::query()->create([
            'code' => 'GB-SC',
            'country_code' => 'GB',
            'name' => 'UK Supreme Court',
            'is_active' => true,
        ]);
        $usJur = Jurisdiction::query()->create([
            'code' => 'US-FED',
            'country_code' => 'US',
            'name' => 'United States Federal Courts',
            'is_active' => true,
        ]);

        // 1. Checkout page must NOT display the optional Cases/Jurisdictions checklist
        $this->get(route('checkout.show', ['plan' => 'starter']))
            ->assertOk()
            ->assertDontSee('Cases / Jurisdictions to Show');

        // 2. User registers via checkout selecting country PK (no explicit jurisdiction_ids)
        $user = User::factory()->create([
            'email' => 'auto.pk@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'firm_id' => null,
            'onboarding' => [
                'preferred_country' => 'PK',
                'preferred_phone' => '+923247087907',
                'preferred_jurisdiction_ids' => [],
            ],
        ]);
        $user->assignRole('Lawyer');

        $sessionId = 'cs_mock_auto_pk_999';
        Subscription::create([
            'user_id' => $user->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_checkout_session_id' => $sessionId,
            'status' => 'incomplete',
        ]);

        $this->get(route('checkout.success', ['mock' => 1, 'session_id' => $sessionId]))->assertOk();

        $lawyer = Lawyer::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('PK', $lawyer->country);
        $this->assertSame([$pkJur->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
        $this->assertNotContains($usJur->id, $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());

        // 3. Switching country on /profile automatically switches assigned jurisdictions to the new country only
        $user->refresh();
        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'phone_country' => 'GB',
                'phone_local' => '7911123456',
                'jurisdiction_ids' => [$pkJur->id], // stale PK id from previous country must be replaced by GB!
            ])
            ->assertRedirect(route('profile.edit'));

        $lawyer->refresh();
        $this->assertSame('GB', $lawyer->country);
        $this->assertSame([$gbJur->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
    }
}

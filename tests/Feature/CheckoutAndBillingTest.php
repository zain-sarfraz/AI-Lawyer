<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Billing\StripePaymentService;
use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Enums\UserStatus;
use App\Models\Client;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer as StripeCustomer;
use Stripe\Event as StripeEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class CheckoutAndBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();

        config([
            'stripe.secret' => 'sk_test_mock_secret',
            'stripe.key' => 'pk_test_mock_key',
            'stripe.webhook_secret' => 'whsec_mock_secret',
            'stripe.prices.starter.monthly' => 'price_starter_mo_test',
            'stripe.prices.growth.monthly' => 'price_growth_mo_test',
            'plans.plans.starter.stripe_price_monthly' => 'price_starter_mo_test',
            'plans.plans.growth.stripe_price_monthly' => 'price_growth_mo_test',
        ]);
    }

    public function test_starter_checkout_creates_user_with_no_firm(): void
    {
        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('createCustomer')
            ->once()
            ->andReturn(StripeCustomer::constructFrom(['id' => 'cus_starter_123']));
        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(StripeSession::constructFrom([
                'id' => 'cs_starter_123',
                'url' => 'https://checkout.stripe.com/pay/cs_starter_123',
            ]));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->post(route('checkout.store'), [
            'plan' => 'starter',
            'interval' => 'monthly',
            'name' => 'John Solo Lawyer',
            'email' => 'john.solo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accept_terms' => '1',
        ]);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_starter_123');

        $this->assertDatabaseHas('users', [
            'email' => 'john.solo@example.com',
            'name' => 'John Solo Lawyer',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment->value,
            'firm_id' => null,
            'stripe_customer_id' => 'cus_starter_123',
        ]);

        $user = User::withoutGlobalScopes()->where('email', 'john.solo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->firm_id);
        $this->assertTrue($user->hasRole('Lawyer'));

        // Firm count must not increase for Starter
        $this->assertEquals(0, Firm::count());

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'status' => 'incomplete',
            'stripe_checkout_session_id' => 'cs_starter_123',
        ]);
    }

    public function test_growth_checkout_creates_user_plus_firm(): void
    {
        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('createCustomer')
            ->once()
            ->andReturn(StripeCustomer::constructFrom(['id' => 'cus_growth_123']));
        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(StripeSession::constructFrom([
                'id' => 'cs_growth_123',
                'url' => 'https://checkout.stripe.com/pay/cs_growth_123',
            ]));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->post(route('checkout.store'), [
            'plan' => 'growth',
            'interval' => 'monthly',
            'name' => 'Partner Alice',
            'email' => 'alice@apexlaw.com',
            'firm_name' => 'Apex Law LLP',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accept_terms' => '1',
        ]);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_growth_123');

        $this->assertDatabaseHas('firms', [
            'firm_name' => 'Apex Law LLP',
            'status' => 'pending',
            'account_type' => 'firm',
            'plan' => 'growth',
        ]);

        $firm = Firm::withoutGlobalScopes()->where('firm_name', 'Apex Law LLP')->first();
        $this->assertNotNull($firm);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@apexlaw.com',
            'firm_id' => $firm->id,
            'account_type' => 'firm',
            'role' => 'firm_admin',
            'status' => UserStatus::PendingPayment->value,
            'stripe_customer_id' => 'cus_growth_123',
        ]);

        $user = User::withoutGlobalScopes()->where('email', 'alice@apexlaw.com')->first();
        $this->assertTrue($user->hasRole('FirmAdmin'));
        $this->assertEquals($user->id, $firm->owner_id);
    }

    public function test_webhook_activates_the_account_and_is_idempotent(): void
    {
        $user = User::factory()->create([
            'email' => 'lawyer.activate@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'stripe_customer_id' => 'cus_act_123',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_customer_id' => 'cus_act_123',
            'stripe_checkout_session_id' => 'cs_act_123',
            'status' => 'incomplete',
        ]);

        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('retrieveSubscription')
            ->andReturn(StripeSubscription::constructFrom([
                'id' => 'sub_act_123',
                'current_period_end' => now()->addMonth()->timestamp,
                'status' => 'active',
            ]));

        $eventData = [
            'id' => 'evt_test_12345',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_act_123',
                    'customer' => 'cus_act_123',
                    'subscription' => 'sub_act_123',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'plan' => 'starter',
                        'interval' => 'monthly',
                    ],
                ],
            ],
        ];

        $mockStripe->shouldReceive('constructWebhookEvent')
            ->andReturn(StripeEvent::constructFrom($eventData));

        $this->app->instance(StripePaymentService::class, $mockStripe);

        // First webhook call: must activate account
        $response = $this->postJson(route('stripe.webhook'), $eventData, [
            'Stripe-Signature' => 't=123,v1=valid_sig',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertEquals(UserStatus::Active, $user->status);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_act_123',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => 'evt_test_12345',
            'type' => 'checkout.session.completed',
        ]);

        // Second webhook call (idempotency): should return already_processed without re-running
        $duplicateResponse = $this->postJson(route('stripe.webhook'), $eventData, [
            'Stripe-Signature' => 't=123,v1=valid_sig',
        ]);

        $duplicateResponse->assertOk();
        $duplicateResponse->assertJson(['status' => 'already_processed']);

        // Assert single event record
        $this->assertEquals(1, StripeWebhookEvent::where('event_id', 'evt_test_12345')->count());
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->andThrow(new SignatureVerificationException('Invalid signature'));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->postJson(route('stripe.webhook'), ['foo' => 'bar'], [
            'Stripe-Signature' => 'invalid',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_pending_payment_user_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'pending.user@example.com',
            'status' => UserStatus::PendingPayment,
            'pending_plan' => 'starter',
            'pending_interval' => 'monthly',
        ]);
        $user->assignRole('Lawyer');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('checkout.show', [
            'plan' => 'starter',
            'interval' => 'monthly',
            'resume' => 1,
            'email' => 'pending.user@example.com',
        ]));
    }

    public function test_firm_isolation_firm_admin_a_cannot_access_firm_b_data(): void
    {
        $firmA = Firm::factory()->create(['firm_name' => 'Firm A Advocates', 'account_type' => 'firm']);
        $firmB = Firm::factory()->create(['firm_name' => 'Firm B Barristers', 'account_type' => 'firm']);

        $adminA = User::factory()->create([
            'firm_id' => $firmA->id,
            'email' => 'admin-a@example.com',
            'account_type' => 'firm',
            'status' => UserStatus::Active,
        ]);
        $adminA->assignRole('FirmAdmin');

        $clientB = app(FirmContext::class)->run($firmB->id, function () {
            return Client::query()->create([
                'name' => 'Confidential Client B',
                'email' => 'clientb@example.com',
                'status' => 'active',
            ]);
        });

        // Firm Admin A queries clients listing -> must not see Firm B client
        $response = $this->actingAs($adminA)->get(route('clients.index'));
        $response->assertOk();
        $response->assertDontSee('Confidential Client B');

        // Direct show route for Client B by Admin A must return 404
        $this->actingAs($adminA)
            ->get(route('clients.show', $clientB))
            ->assertNotFound();
    }

    public function test_resume_payment_when_pending_payment_user_submits_checkout(): void
    {
        $existing = User::factory()->create([
            'email' => 'resume.me@example.com',
            'password' => bcrypt('secret123'),
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'stripe_customer_id' => 'cus_existing_999',
        ]);

        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(StripeSession::constructFrom([
                'id' => 'cs_resume_999',
                'url' => 'https://checkout.stripe.com/pay/cs_resume_999',
            ]));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->post(route('checkout.store'), [
            'plan' => 'starter',
            'interval' => 'monthly',
            'name' => 'Resume User',
            'email' => 'resume.me@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'accept_terms' => '1',
        ]);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_resume_999');

        $this->assertEquals(1, User::withoutGlobalScopes()->where('email', 'resume.me@example.com')->count());
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $existing->id,
            'stripe_checkout_session_id' => 'cs_resume_999',
        ]);
    }

    public function test_logged_in_active_user_is_redirected_from_checkout(): void
    {
        $user = User::factory()->create([
            'email' => 'active.user@example.com',
            'status' => UserStatus::Active,
            'stripe_customer_id' => 'cus_active_123',
        ]);
        $user->assignRole('Lawyer');

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_customer_id' => 'cus_active_123',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('checkout.show', ['plan' => 'starter']));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_invoice_payment_failed_webhook_marks_status_past_due(): void
    {
        $user = User::factory()->create([
            'email' => 'failed.invoice@example.com',
            'status' => UserStatus::Active,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_subscription_id' => 'sub_fail_123',
            'status' => 'active',
        ]);

        $eventData = [
            'id' => 'evt_fail_123',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_fail_123',
                ],
            ],
        ];

        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->andReturn(StripeEvent::constructFrom($eventData));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->postJson(route('stripe.webhook'), $eventData, [
            'Stripe-Signature' => 'valid_sig',
        ]);

        $response->assertOk();

        $user = User::withoutGlobalScopes()->find($user->id);
        $this->assertEquals(UserStatus::PastDue, $user->status);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'past_due',
        ]);
    }

    public function test_subscription_deleted_webhook_marks_status_cancelled(): void
    {
        $user = User::factory()->create([
            'email' => 'cancelled.sub@example.com',
            'status' => UserStatus::Active,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_subscription_id' => 'sub_del_123',
            'status' => 'active',
        ]);

        $eventData = [
            'id' => 'evt_del_123',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_del_123',
                ],
            ],
        ];

        $mockStripe = Mockery::mock(StripePaymentService::class)->makePartial();
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->andReturn(StripeEvent::constructFrom($eventData));
        $this->app->instance(StripePaymentService::class, $mockStripe);

        $response = $this->postJson(route('stripe.webhook'), $eventData, [
            'Stripe-Signature' => 'valid_sig',
        ]);

        $response->assertOk();

        $user = User::withoutGlobalScopes()->find($user->id);
        $this->assertEquals(UserStatus::Cancelled, $user->status);

        // Data must not be deleted from subscriptions table
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_sales_inquiry_can_be_submitted_for_pilot_plan(): void
    {
        $response = $this->postJson(route('sales-inquiries.store'), [
            'name' => 'Managing Partner Robert',
            'email' => 'robert@bigfirm.com',
            'firm_name' => 'Big Firm Associates',
            'message' => 'Interested in the Pilot locked rate for 12 lawyers.',
            'plan' => 'pilot',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales_inquiries', [
            'email' => 'robert@bigfirm.com',
            'plan' => 'pilot',
            'firm_name' => 'Big Firm Associates',
        ]);
    }

    public function test_prune_unpaid_command_deletes_old_pending_accounts(): void
    {
        $oldUser = User::factory()->create([
            'email' => 'abandoned@example.com',
            'status' => UserStatus::PendingPayment,
            'created_at' => now()->subDays(8),
        ]);

        $recentUser = User::factory()->create([
            'email' => 'recent.pending@example.com',
            'status' => UserStatus::PendingPayment,
            'created_at' => now()->subDays(2),
        ]);

        $this->artisan('users:prune-unpaid --days=7')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $oldUser->id]);
        $this->assertDatabaseHas('users', ['id' => $recentUser->id]);
    }

    public function test_onboarding_step_can_be_completed_and_dismissed(): void
    {
        $user = User::factory()->create([
            'email' => 'onboard@example.com',
            'account_type' => 'individual',
            'status' => UserStatus::Active,
        ]);
        $user->assignRole('Lawyer');

        $this->actingAs($user)->postJson(route('onboarding.step'), [
            'step' => 'profile',
        ])->assertOk()->assertJson(['completed' => true]);

        $user->refresh();
        $this->assertTrue($user->isStepCompleted('profile'));

        $this->actingAs($user)->postJson(route('onboarding.dismiss'))
            ->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue($user->isOnboardingDismissed());
    }

    public function test_individual_lawyer_can_access_availability_ai_receptionist_and_settings(): void
    {
        $user = User::factory()->create([
            'email' => 'individual.workspace@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::Active,
            'firm_id' => null,
        ]);
        $user->assignRole('Lawyer');

        // Test 1: availability
        $resAvailability = $this->actingAs($user)->get(route('schedule.availability'));
        $resAvailability->assertOk();

        // Test 2: ai-receptionist
        $resAi = $this->actingAs($user)->get(route('ai.show'));
        $resAi->assertOk();

        // Test 3: settings
        $resSettings = $this->actingAs($user)->get(route('settings.edit'));
        $resSettings->assertOk();
    }

    public function test_mock_checkout_success_and_status_activate_individual_lawyer_and_create_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'mock.solo@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'firm_id' => null,
            'pending_plan' => 'starter',
            'pending_interval' => 'monthly',
        ]);
        $user->assignRole('Lawyer');

        $sessionId = 'cs_mock_test1234567890';
        Subscription::create([
            'user_id' => $user->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_checkout_session_id' => $sessionId,
            'status' => 'incomplete',
        ]);

        $response = $this->get(route('checkout.success', ['mock' => 1, 'session_id' => $sessionId]));
        $response->assertOk();

        $user->refresh();
        $this->assertEquals(UserStatus::Active, $user->status);
        $this->assertDatabaseHas('lawyers', [
            'user_id' => $user->id,
            'firm_id' => null,
            'status' => 'active',
        ]);

        // Status endpoint on a second mock subscription must also confirm cleanly
        $user2 = User::factory()->create([
            'email' => 'mock.solo2@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'firm_id' => null,
        ]);
        $user2->assignRole('Lawyer');
        $sessionId2 = 'cs_mock_test0987654321';
        Subscription::create([
            'user_id' => $user2->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_checkout_session_id' => $sessionId2,
            'status' => 'incomplete',
        ]);

        $statusRes = $this->getJson(route('checkout.status', ['mock' => 1, 'session_id' => $sessionId2]));
        $statusRes->assertOk()->assertJson(['status' => 'confirmed']);
        $this->assertDatabaseHas('lawyers', ['user_id' => $user2->id]);
    }
}

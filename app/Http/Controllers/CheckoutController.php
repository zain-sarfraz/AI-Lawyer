<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Billing\StripePaymentService;
use App\Domain\Lawyers\LawyerService;
use App\Enums\UserStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PlanCatalog $plans,
        private readonly StripePaymentService $stripe,
        private readonly LawyerService $lawyers,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasActiveSubscription()) {
                return redirect()->route($user->homeRoute())->with('info', 'Your subscription is already active.');
            }
        }

        $planId = (string) $request->query('plan', 'starter');
        $interval = $this->plans->normalizeInterval((string) $request->query('interval', config('plans.default_interval', 'monthly')));

        if (! $this->plans->exists($planId)) {
            return redirect()->to('/#pricing')->with('error', 'Please choose a valid plan.');
        }

        $planConfig = $this->plans->get($planId);

        // Pilot requires contact sales unless online checkout is enabled
        if ($planId === 'pilot' && ! ($planConfig['checkout_enabled'] ?? false)) {
            return redirect()->to('/#pricing')->with('info', 'The Pilot plan is available through sales consultation.');
        }

        $summary = $this->plans->checkoutSummary($planId, $interval);

        return view('checkout.show', [
            'summary' => $summary,
            'stripeKey' => config('stripe.key'),
            'idempotencyKey' => (string) Str::uuid(),
            'resume' => (bool) $request->query('resume', false),
            'pendingEmail' => $request->query('email'),
            'activeJurisdictions' => Jurisdiction::activeGroupedByCountry(),
            'countryLabels' => PhoneNormalizer::countryLabels(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasActiveSubscription()) {
                return redirect()->route($user->homeRoute())->with('info', 'Your subscription is already active.');
            }
        }

        $data = $request->validated();
        $planId = $data['plan'];
        $interval = $this->plans->normalizeInterval($data['interval']);
        $summary = $this->plans->checkoutSummary($planId, $interval);
        $email = strtolower(trim($data['email']));

        // Pilot contact sales guard
        if ($planId === 'pilot' && ! ($summary['checkout_enabled'] ?? config('plans.pilot_checkout_enabled', false))) {
            return redirect()->to('/#pricing')->with('info', 'The Pilot plan requires Contact Sales.');
        }

        // Validate Stripe Price ID exists in configuration (fallback to standard identifier)
        $priceId = $this->plans->stripePriceId($planId, $interval) ?: ('price_' . $planId . '_' . $interval);

        // Check if an existing account exists with this email
        $existingUser = User::withoutGlobalScopes()->where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->status !== UserStatus::PendingPayment) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Account already exists, please log in.']);
            }

            // User is pending_payment: verify password to resume checkout
            if (! Hash::check($data['password'], $existingUser->password)) {
                return back()
                    ->withInput()
                    ->withErrors(['password' => 'This account has a pending payment. Please enter your existing password to resume payment.']);
            }

            $user = $existingUser;
            $user->pending_plan = $planId;
            $user->pending_interval = $interval;
            $user->save();
            $firm = $user->firm;
        } else {
            // New user registration
            $rawCountry = strtoupper(trim((string) ($data['country'] ?? '')));
            $preferredCountry = in_array($rawCountry, PhoneNormalizer::supportedCountries(), true) ? $rawCountry : null;
            $preferredPhone = trim((string) ($data['phone'] ?? '')) ?: null;
            if ($preferredPhone !== null && $preferredCountry !== null && ! str_starts_with($preferredPhone, '+')) {
                try {
                    $normalized = PhoneNormalizer::normalize($preferredCountry, $preferredPhone);
                    if ($normalized !== '') {
                        $preferredPhone = $normalized;
                    }
                } catch (\InvalidArgumentException) {
                    // Keep entered phone as-is when checkout optional phone does not match strict local length
                }
            }
            $onboardingPrefs = [
                'preferred_country' => $preferredCountry,
                'preferred_phone' => $preferredPhone,
                'preferred_jurisdiction_ids' => array_values(array_map('intval', $data['jurisdiction_ids'] ?? [])),
            ];

            try {
                [$user, $firm] = DB::transaction(function () use ($data, $summary, $email, $planId, $interval, $onboardingPrefs, $preferredPhone) {
                    if ($summary['account_type'] === 'individual') {
                        $user = User::create([
                            'account_type' => 'individual',
                            'role' => 'individual_lawyer',
                            'status' => UserStatus::PendingPayment,
                            'name' => $data['name'],
                            'email' => $email,
                            'password' => Hash::make($data['password']),
                            'pending_plan' => $planId,
                            'pending_interval' => $interval,
                            'onboarding' => $onboardingPrefs,
                        ]);

                        $user->assignRole('Lawyer');

                        return [$user, null];
                    }

                    // Law Firm Account
                    $firmName = trim((string) ($data['firm_name'] ?? ''));
                    if ($firmName === '') {
                        $firmName = $data['name'] . ' Law Firm';
                    }

                    $firm = Firm::create([
                        'name' => $firmName,
                        'firm_name' => $firmName,
                        'status' => 'pending',
                        'account_type' => 'firm',
                        'plan' => $planId,
                        'billing_interval' => $interval,
                        'subscription_status' => 'incomplete',
                        'phone' => $preferredPhone,
                        'email' => $email,
                    ]);

                    $user = User::create([
                        'firm_id' => $firm->id,
                        'account_type' => 'firm',
                        'role' => 'firm_admin',
                        'status' => UserStatus::PendingPayment,
                        'name' => $data['name'],
                        'email' => $email,
                        'password' => Hash::make($data['password']),
                        'pending_plan' => $planId,
                        'pending_interval' => $interval,
                        'onboarding' => $onboardingPrefs,
                    ]);

                    $firm->owner_id = $user->id;
                    $firm->save();
                    $user->assignRole('FirmAdmin');

                    return [$user, $firm];
                });
            } catch (Throwable $e) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Unable to prepare account: ' . $e->getMessage()]);
            }
        }

        // Ensure Stripe Customer exists
        try {
            if (empty($user->stripe_customer_id)) {
                $customer = $this->stripe->createCustomer([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'firm_id' => $firm ? (string) $firm->id : '',
                        'account_type' => $user->account_type,
                    ],
                ], $data['idempotency_key'] ?? null);

                $user->stripe_customer_id = $customer->id;
                $user->save();

                if ($firm) {
                    $firm->stripe_customer_id = $customer->id;
                    $firm->save();
                }
            }

            // Create Hosted Stripe Checkout Session
            $session = $this->stripe->createCheckoutSession([
                'customer_id' => $user->stripe_customer_id,
                'price_id' => $priceId,
                'user_id' => $user->id,
                'firm_id' => $firm?->id,
                'plan' => $planId,
                'interval' => $interval,
            ], $data['idempotency_key'] ?? null);

            // Record pending subscription with checkout session id
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'firm_id' => $firm?->id,
                    'plan' => $planId,
                    'interval' => $interval,
                    'stripe_customer_id' => $user->stripe_customer_id,
                    'stripe_checkout_session_id' => $session->id,
                    'status' => 'incomplete',
                ]
            );

            return redirect()->away($session->url);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'Stripe Checkout Error: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request): View|RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if (blank($sessionId)) {
            return redirect()->to('/#pricing')->with('error', 'Session ID missing.');
        }

        $subscription = Subscription::with(['user', 'firm'])
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $subscription) {
            return view('checkout.success', [
                'status' => 'failed',
                'message' => 'Subscription session not found. If your card was charged, please contact support.',
                'sessionId' => $sessionId,
                'summary' => null,
            ]);
        }

        $summary = $this->plans->checkoutSummary($subscription->plan, $subscription->interval);

        // Auto-confirm in mock/dummy mode
        if (! $subscription->isActive() && ($request->query('mock') || str_starts_with($sessionId, 'cs_mock_') || $this->stripe->isDummyMode())) {
            $this->confirmMockSubscription($subscription, $sessionId);
        }

        if ($subscription->isActive()) {
            // Log in the user if confirmed
            if (! Auth::check() && $subscription->user) {
                Auth::login($subscription->user);
                $request->session()->regenerate();
            }

            return view('checkout.success', [
                'status' => 'confirmed',
                'message' => 'Payment successful! Your account is active.',
                'sessionId' => $sessionId,
                'subscription' => $subscription,
                'summary' => $summary,
                'dashboardUrl' => route($subscription->user?->homeRoute() ?? 'dashboard'),
            ]);
        }

        // Webhook has not processed yet - show pending polling state
        return view('checkout.success', [
            'status' => 'pending',
            'message' => 'Processing payment confirmation...',
            'sessionId' => $sessionId,
            'subscription' => $subscription,
            'summary' => $summary,
            'dashboardUrl' => route($subscription->user?->homeRoute() ?? 'dashboard'),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if (blank($sessionId)) {
            return response()->json(['status' => 'failed', 'message' => 'Missing session_id'], 400);
        }

        $subscription = Subscription::with(['user', 'firm'])
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $subscription) {
            return response()->json(['status' => 'failed', 'message' => 'Session not found'], 404);
        }

        // Auto-confirm in mock/dummy mode
        if (! $subscription->isActive() && ($request->query('mock') || str_starts_with($sessionId, 'cs_mock_') || $this->stripe->isDummyMode())) {
            $this->confirmMockSubscription($subscription, $sessionId);
        }

        if ($subscription->isActive()) {
            if (! Auth::check() && $subscription->user) {
                Auth::login($subscription->user);
                $request->session()->regenerate();
            }

            return response()->json([
                'status' => 'confirmed',
                'redirect_url' => route(Auth::user()?->homeRoute() ?? 'dashboard'),
            ]);
        }

        if ($subscription->status === 'incomplete') {
            return response()->json(['status' => 'pending']);
        }

        return response()->json(['status' => 'failed', 'message' => 'Payment failed or cancelled']);
    }

    private function confirmMockSubscription(Subscription $subscription, string $sessionId): void
    {
        $subscription->status = 'active';
        $subscription->stripe_subscription_id = 'sub_mock_' . substr(md5($sessionId), 0, 14);
        $subscription->current_period_end = now()->addMonth();
        $subscription->save();

        $user = $subscription->user;
        if ($user) {
            $user->status = UserStatus::Active;
            $user->email_verified_at ??= now();
            $user->pending_plan = null;
            $user->pending_interval = null;
            $user->save();

            if ($user->isIndividual() || $user->role === 'individual_lawyer') {
                $this->lawyers->provisionForActivatedUser($user, $user->firm_id, 'Individual Lawyer');
            } elseif ($user->isFirmAdmin()) {
                $this->lawyers->provisionForActivatedUser($user, $user->firm_id, 'Firm Admin');
            }
        }

        $firm = $subscription->firm;
        if ($firm) {
            $firm->status = 'active';
            $firm->subscription_status = 'active';
            $firm->subscribed_at = $firm->subscribed_at ?? now();
            $firm->save();
        }
    }
}

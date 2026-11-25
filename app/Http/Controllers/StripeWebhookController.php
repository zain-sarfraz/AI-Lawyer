<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Billing\StripePaymentService;
use App\Domain\Lawyers\LawyerService;
use App\Enums\UserStatus;
use App\Mail\SubscriptionWelcomeMail;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripe,
        private readonly PlanCatalog $plans,
        private readonly LawyerService $lawyers,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature');

        try {
            $event = $this->stripe->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed: ' . $e->getMessage());

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (Throwable $e) {
            Log::error('Stripe webhook payload error: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Idempotency: ignore if this event was already processed
        if (StripeWebhookEvent::where('event_id', $event->id)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        StripeWebhookEvent::create([
            'event_id' => $event->id,
            'type' => $event->type,
            'processed_at' => now(),
        ]);

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
                'invoice.paid' => $this->handleInvoicePaid($event->data->object),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
                default => Log::info("Unhandled Stripe webhook event: {$event->type}"),
            };
        } catch (Throwable $e) {
            Log::error("Error handling Stripe event [{$event->type}]: " . $e->getMessage(), [
                'event_id' => $event->id,
                'exception' => $e,
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success']);
    }

    private function handleCheckoutSessionCompleted(object $session): void
    {
        $metadata = (array) ($session->metadata ?? []);
        $userId = $metadata['user_id'] ?? null;
        $firmId = ! empty($metadata['firm_id']) ? (int) $metadata['firm_id'] : null;
        $plan = $metadata['plan'] ?? 'starter';
        $interval = $metadata['interval'] ?? 'monthly';
        $customerId = (string) ($session->customer ?? '');
        $subscriptionId = (string) ($session->subscription ?? '');
        $sessionId = (string) ($session->id ?? '');

        // Fetch current period end from Stripe subscription if available
        $currentPeriodEnd = now()->addMonth();
        if (! empty($subscriptionId)) {
            try {
                $stripeSub = $this->stripe->retrieveSubscription($subscriptionId);
                if (! empty($stripeSub->current_period_end)) {
                    $currentPeriodEnd = Carbon::createFromTimestamp($stripeSub->current_period_end);
                }
            } catch (Throwable $e) {
                Log::warning("Could not fetch subscription period end: {$e->getMessage()}");
            }
        }

        DB::transaction(function () use ($userId, $firmId, $plan, $interval, $customerId, $subscriptionId, $sessionId, $currentPeriodEnd) {
            // Find user by ID or by customer ID
            $user = null;
            if ($userId) {
                $user = User::withoutGlobalScopes()->find($userId);
            }
            if (! $user && $customerId) {
                $user = User::withoutGlobalScopes()->where('stripe_customer_id', $customerId)->first();
            }

            if (! $user) {
                Log::warning("Checkout completed but User not found for Stripe customer: {$customerId}");

                return;
            }

            // Activate User
            $user->status = UserStatus::Active;
            $user->email_verified_at ??= now();
            $user->pending_plan = null;
            $user->pending_interval = null;
            $user->stripe_customer_id = $customerId;
            $user->save();

            // Activate Firm if this is a firm account
            $firm = null;
            $targetFirmId = $firmId ?: $user->firm_id;
            if ($targetFirmId) {
                $firm = Firm::withoutGlobalScopes()->find($targetFirmId);
                if ($firm) {
                    $firm->status = 'active';
                    $firm->subscription_status = 'active';
                    $firm->plan = $plan;
                    $firm->billing_interval = $interval;
                    $firm->stripe_customer_id = $customerId;
                    $firm->stripe_subscription_id = $subscriptionId;
                    $firm->subscribed_at = now();
                    $firm->save();

                    // Ensure firm admin lawyer profile exists
                    $this->lawyers->provisionForActivatedUser($user, (int) $firm->id, 'Firm Admin');
                }
            } else {
                // Individual lawyer profile provisioned on activation
                $this->lawyers->provisionForActivatedUser($user, null, 'Individual Lawyer');
            }

            // Save active subscription record
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'firm_id' => $firm?->id,
                    'plan' => $plan,
                    'interval' => $interval,
                    'stripe_customer_id' => $customerId,
                    'stripe_subscription_id' => $subscriptionId ?: null,
                    'stripe_checkout_session_id' => $sessionId ?: null,
                    'status' => 'active',
                    'current_period_end' => $currentPeriodEnd,
                ]
            );

            // Send Welcome Email if mail system is configured
            try {
                $summary = $this->plans->checkoutSummary($plan, $interval);
                Mail::to($user->email)->send(new SubscriptionWelcomeMail(
                    $user,
                    $summary['name'],
                    $interval,
                    $summary['amount_label']
                ));
            } catch (Throwable $e) {
                Log::info("Welcome email not dispatched (mail driver or transport): {$e->getMessage()}");
            }
        });
    }

    private function handleInvoicePaid(object $invoice): void
    {
        $subscriptionId = (string) ($invoice->subscription ?? '');
        if (blank($subscriptionId)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return;
        }

        // Update period end if provided in invoice lines
        $periodEnd = null;
        if (! empty($invoice->lines?->data[0]?->period?->end)) {
            $periodEnd = Carbon::createFromTimestamp((int) $invoice->lines->data[0]->period->end);
        }

        $subscription->status = 'active';
        if ($periodEnd) {
            $subscription->current_period_end = $periodEnd;
        }
        $subscription->save();

        $user = $subscription->user_id ? User::withoutGlobalScopes()->find($subscription->user_id) : null;
        if ($user) {
            $user->status = UserStatus::Active;
            $user->save();
        }

        $firm = $subscription->firm_id ? Firm::withoutGlobalScopes()->find($subscription->firm_id) : null;
        if ($firm) {
            $firm->status = 'active';
            $firm->subscription_status = 'active';
            $firm->save();
        }
    }

    private function handleInvoicePaymentFailed(object $invoice): void
    {
        $subscriptionId = (string) ($invoice->subscription ?? '');
        if (blank($subscriptionId)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return;
        }

        $subscription->status = 'past_due';
        $subscription->save();

        $user = $subscription->user_id ? User::withoutGlobalScopes()->find($subscription->user_id) : null;
        if ($user) {
            $user->status = UserStatus::PastDue;
            $user->save();
        }

        $firm = $subscription->firm_id ? Firm::withoutGlobalScopes()->find($subscription->firm_id) : null;
        if ($firm) {
            $firm->subscription_status = 'past_due';
            $firm->save();
        }

        Log::warning("Subscription payment failed for subscription: {$subscriptionId}");
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $subscriptionId = (string) ($subscription->id ?? '');
        if (blank($subscriptionId)) {
            return;
        }

        $sub = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $sub) {
            return;
        }

        $sub->status = 'cancelled';
        $sub->save();

        // Restrict access while keeping all data safe
        $user = $sub->user_id ? User::withoutGlobalScopes()->find($sub->user_id) : null;
        if ($user) {
            $user->status = UserStatus::Cancelled;
            $user->save();
        }

        $firm = $sub->firm_id ? Firm::withoutGlobalScopes()->find($sub->firm_id) : null;
        if ($firm) {
            $firm->subscription_status = 'cancelled';
            $firm->save();
        }
    }

    private function handleSubscriptionUpdated(object $subscription): void
    {
        $subscriptionId = (string) ($subscription->id ?? '');
        if (blank($subscriptionId)) {
            return;
        }

        $sub = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $sub) {
            return;
        }

        $stripeStatus = (string) ($subscription->status ?? 'active');
        $sub->status = $stripeStatus;

        if (! empty($subscription->current_period_end)) {
            $sub->current_period_end = Carbon::createFromTimestamp((int) $subscription->current_period_end);
        }

        $sub->save();

        $user = $sub->user_id ? User::withoutGlobalScopes()->find($sub->user_id) : null;
        if ($user) {
            if ($stripeStatus === 'active') {
                $user->status = UserStatus::Active;
            } elseif ($stripeStatus === 'past_due') {
                $user->status = UserStatus::PastDue;
            } elseif (in_array($stripeStatus, ['canceled', 'unpaid'], true)) {
                $user->status = UserStatus::Cancelled;
            }
            $user->save();
        }

        $firm = $sub->firm_id ? Firm::withoutGlobalScopes()->find($sub->firm_id) : null;
        if ($firm) {
            $firm->subscription_status = $stripeStatus;
            $firm->save();
        }
    }
}

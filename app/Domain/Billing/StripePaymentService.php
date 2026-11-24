<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use RuntimeException;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer as StripeCustomer;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook as StripeWebhook;

class StripePaymentService
{
    private ?StripeClient $client = null;

    public function client(): StripeClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $secret = (string) config('stripe.secret');
        if (blank($secret)) {
            throw new RuntimeException('Stripe secret key is missing. Please set STRIPE_SECRET in your .env file.');
        }

        $this->client = new StripeClient($secret);

        return $this->client;
    }

    public function setClient(StripeClient $client): void
    {
        $this->client = $client;
    }

    public function isConfigured(): bool
    {
        $secret = (string) config('stripe.secret');

        return ! blank($secret) && ! str_contains($secret, 'dummy') && ! str_contains($secret, 'placeholder');
    }

    public function isDummyMode(): bool
    {
        return config('stripe.mode') === 'dummy' || ! $this->isConfigured();
    }

    /**
     * @param  array{email: string, name?: ?string, metadata?: array<string, mixed>}  $params
     */
    public function createCustomer(array $params, ?string $idempotencyKey = null): StripeCustomer
    {
        if ($this->isDummyMode()) {
            return StripeCustomer::constructFrom([
                'id' => 'cus_mock_' . substr(md5($params['email']), 0, 14),
                'email' => $params['email'],
                'name' => $params['name'] ?? null,
                'metadata' => $params['metadata'] ?? [],
            ]);
        }

        $options = [];
        if ($idempotencyKey) {
            $options['idempotency_key'] = 'cus_' . $idempotencyKey;
        }

        return $this->client()->customers->create([
            'email' => $params['email'],
            'name' => $params['name'] ?? null,
            'metadata' => $params['metadata'] ?? [],
        ], $options);
    }

    /**
     * Create a Stripe Checkout Session for subscription checkout.
     *
     * @param  array{
     *     customer_id: string,
     *     price_id: string,
     *     user_id: int|string,
     *     firm_id?: int|string|null,
     *     plan: string,
     *     interval: string
     * }  $params
     */
    public function createCheckoutSession(array $params, ?string $idempotencyKey = null): StripeSession
    {
        $priceId = $params['price_id'] ?? '';
        if (blank($priceId)) {
            $priceId = 'price_' . ($params['plan'] ?? 'starter') . '_' . ($params['interval'] ?? 'monthly');
        }

        if ($this->isDummyMode()) {
            $sessionId = 'cs_mock_' . bin2hex(random_bytes(10));

            return StripeSession::constructFrom([
                'id' => $sessionId,
                'customer' => $params['customer_id'] ?? 'cus_mock_local',
                'mode' => 'subscription',
                'url' => url('/checkout/success?session_id=' . $sessionId . '&mock=1'),
            ]);
        }

        $options = [];
        if ($idempotencyKey) {
            $options['idempotency_key'] = 'cs_' . $idempotencyKey;
        }

        $metadata = [
            'user_id' => (string) $params['user_id'],
            'firm_id' => isset($params['firm_id']) && $params['firm_id'] ? (string) $params['firm_id'] : '',
            'plan' => (string) $params['plan'],
            'interval' => (string) $params['interval'],
        ];

        return $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $params['customer_id'],
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
            'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/checkout?plan=' . $params['plan'] . '&interval=' . $params['interval']),
        ], $options);
    }

    public function retrieveCheckoutSession(string $sessionId): StripeSession
    {
        if ($this->isDummyMode() || str_starts_with($sessionId, 'cs_mock_')) {
            return StripeSession::constructFrom([
                'id' => $sessionId,
                'customer' => 'cus_mock_local',
                'subscription' => 'sub_mock_' . substr(md5($sessionId), 0, 14),
                'payment_status' => 'paid',
                'status' => 'complete',
            ]);
        }

        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'customer'],
        ]);
    }

    public function retrieveSubscription(string $subscriptionId): StripeSubscription
    {
        if ($this->isDummyMode() || str_starts_with($subscriptionId, 'sub_mock_')) {
            return StripeSubscription::constructFrom([
                'id' => $subscriptionId,
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
            ]);
        }

        return $this->client()->subscriptions->retrieve($subscriptionId);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): StripeEvent
    {
        $webhookSecret = (string) config('stripe.webhook_secret');
        if (blank($webhookSecret)) {
            throw new RuntimeException('Stripe webhook secret is missing. Please set STRIPE_WEBHOOK_SECRET in your .env file.');
        }

        return StripeWebhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }
}

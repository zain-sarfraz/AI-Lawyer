<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use InvalidArgumentException;

class PlanCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return config('plans.plans', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $planId): array
    {
        $plan = $this->all()[$planId] ?? null;
        if (! is_array($plan)) {
            throw new InvalidArgumentException("Unknown plan [{$planId}].");
        }

        return $plan;
    }

    public function exists(string $planId): bool
    {
        return isset($this->all()[$planId]);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->all());
    }

    public function priceCents(string $planId, string $interval): int
    {
        $plan = $this->get($planId);
        $interval = $this->normalizeInterval($interval);

        return $interval === 'yearly'
            ? (int) $plan['yearly_price']
            : (int) $plan['monthly_price'];
    }

    public function formatMoney(int $cents): string
    {
        return '$'.number_format($cents / 100, $cents % 100 === 0 ? 0 : 2);
    }

    public function normalizeInterval(string $interval): string
    {
        return $interval === 'yearly' ? 'yearly' : 'monthly';
    }

    public function stripePriceId(string $planId, string $interval): ?string
    {
        $plan = $this->get($planId);
        $interval = $this->normalizeInterval($interval);

        $priceId = $interval === 'yearly'
            ? ($plan['stripe_price_yearly'] ?? null)
            : ($plan['stripe_price_monthly'] ?? null);

        return is_string($priceId) && $priceId !== '' ? $priceId : null;
    }

    public function anyYearlyPriceConfigured(): bool
    {
        foreach (['starter', 'growth', 'pilot'] as $id) {
            if ($this->exists($id) && ! empty($this->stripePriceId($id, 'yearly'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *   id: string,
     *   name: string,
     *   account_type: string,
     *   account_label: string,
     *   tagline: string,
     *   interval: string,
     *   amount_cents: int,
     *   amount_label: string,
     *   interval_label: string,
     *   bill_note: string,
     *   max_lawyers: ?int,
     *   features: list<string>,
     *   conditions: list<string>,
     *   stripe_price_id: ?string
     * }
     */
    public function checkoutSummary(string $planId, string $interval): array
    {
        $plan = $this->get($planId);
        $interval = $this->normalizeInterval($interval);
        $cents = $this->priceCents($planId, $interval);

        return [
            'id' => $plan['id'],
            'name' => $plan['name'],
            'account_type' => $plan['account_type'],
            'account_label' => $plan['account_label'],
            'tagline' => $plan['tagline'],
            'interval' => $interval,
            'amount_cents' => $cents,
            'amount_label' => $this->formatMoney($cents),
            'interval_label' => $interval === 'yearly' ? '/year' : '/month',
            'bill_note' => $interval === 'yearly' ? 'Billed yearly' : 'Billed monthly',
            'max_lawyers' => $plan['max_lawyers'],
            'features' => $plan['features'],
            'conditions' => $plan['conditions'],
            'stripe_price_id' => $this->stripePriceId($planId, $interval),
        ];
    }
}

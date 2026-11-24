<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS subscription plans
    |--------------------------------------------------------------------------
    | Prices are stored in cents (USD). Stripe Price IDs come from .env.
    | Starter = individual (1 lawyer). Growth/Pilot = firm (unlimited seats).
    | Pilot online checkout is off by default (Contact Sales); set
    | STRIPE_PILOT_CHECKOUT_ENABLED=true to enable.
    */

    'default_interval' => 'monthly',

    'pilot_checkout_enabled' => (bool) env('STRIPE_PILOT_CHECKOUT_ENABLED', false),

    'plans' => [
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter',
            'account_type' => 'individual',
            'account_label' => 'Individual Lawyer Account',
            'tagline' => 'For individual lawyers.',
            'monthly_price' => 29900,
            'yearly_price' => 299000,
            'max_lawyers' => 1,
            'checkout_enabled' => true,
            'contact_sales' => false,
            'stripe_price_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY', 'price_starter_monthly'),
            'stripe_price_yearly' => env('STRIPE_PRICE_STARTER_YEARLY', 'price_starter_yearly'),
            'features' => [
                'AI phone receptionist',
                'Appointments & calendar',
                'Client, lead & case management',
                'Multi-country case law research',
                '1 lawyer seat only',
            ],
            'conditions' => [
                'Billed according to the selected interval.',
                'Account type: Individual — one lawyer seat.',
                'You cannot add additional lawyers on Starter.',
                'Cancel anytime; access continues until the end of the paid period.',
            ],
        ],
        'growth' => [
            'id' => 'growth',
            'name' => 'Growth',
            'account_type' => 'firm',
            'account_label' => 'Law Firm Account',
            'tagline' => 'For established law firms.',
            'monthly_price' => 59900,
            'yearly_price' => 599000,
            'max_lawyers' => null,
            'checkout_enabled' => true,
            'contact_sales' => false,
            'stripe_price_monthly' => env('STRIPE_PRICE_GROWTH_MONTHLY', 'price_growth_monthly'),
            'stripe_price_yearly' => env('STRIPE_PRICE_GROWTH_YEARLY', 'price_growth_yearly'),
            'features' => [
                'Everything in Starter',
                'Multi-lawyer firm workspace',
                'Role-based access & audit logs',
                'Reports & firm settings',
                'Unlimited lawyer seats',
            ],
            'conditions' => [
                'Billed according to the selected interval.',
                'Account type: Firm — shared multi-lawyer workspace.',
                'Full firm CRM, reports, settings, and audit access.',
                'Cancel anytime; access continues until the end of the paid period.',
            ],
        ],
        'pilot' => [
            'id' => 'pilot',
            'name' => 'Pilot',
            'account_type' => 'firm',
            'account_label' => 'Law Firm Account',
            'tagline' => 'Growth features, rate locked for 12 months.',
            'monthly_price' => 40000,
            'yearly_price' => 480000,
            'max_lawyers' => null,
            'checkout_enabled' => (bool) env('STRIPE_PILOT_CHECKOUT_ENABLED', false),
            'contact_sales' => true,
            'rate_locked' => true,
            'stripe_price_monthly' => null,
            'stripe_price_yearly' => env('STRIPE_PRICE_PILOT_YEARLY', 'price_pilot_yearly'),
            'features' => [
                'All Growth features',
                'Yearly billing at $4,800',
                'Equivalent to $400/month',
                'Rate locked for 12 months',
                'Unlimited lawyer seats',
            ],
            'conditions' => [
                'Billed yearly at a locked rate for 12 months.',
                'Account type: Firm — shared multi-lawyer workspace.',
                'Online checkout is disabled by default — Contact Sales.',
                'Enable STRIPE_PILOT_CHECKOUT_ENABLED to sell Pilot online.',
            ],
        ],
    ],
];

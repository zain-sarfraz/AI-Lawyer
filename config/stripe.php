<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    | STRIPE_MODE=dummy skips live API and simulates Checkout + webhook locally.
    | Keys and Price IDs must come from .env — never hardcode secrets.
    */

    'mode' => env('STRIPE_MODE', 'dummy'),

    'key' => env('STRIPE_KEY', 'pk_test_dummy'),

    'secret' => env('STRIPE_SECRET', 'sk_test_dummy'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', 'whsec_dummy'),

    'currency' => env('STRIPE_CURRENCY', 'usd'),

    'prices' => [
        'starter' => [
            'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY', 'price_starter_monthly'),
            'yearly' => env('STRIPE_PRICE_STARTER_YEARLY', 'price_starter_yearly'),
        ],
        'growth' => [
            'monthly' => env('STRIPE_PRICE_GROWTH_MONTHLY', 'price_growth_monthly'),
            'yearly' => env('STRIPE_PRICE_GROWTH_YEARLY', 'price_growth_yearly'),
        ],
        'pilot' => [
            'monthly' => null,
            'yearly' => env('STRIPE_PRICE_PILOT_YEARLY', 'price_pilot_yearly'),
        ],
    ],
];

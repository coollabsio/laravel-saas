<?php

return [
    'self_hosted' => env('SELF_HOSTED', false),

    'require_subscription' => env('REQUIRE_SUBSCRIPTION', false),

    'models' => [
        'team' => Coollabsio\LaravelSaas\Models\Team::class,
        'team_invitation' => Coollabsio\LaravelSaas\Models\TeamInvitation::class,
        'user' => App\Models\User::class,
        'instance_settings' => Coollabsio\LaravelSaas\Models\InstanceSettings::class,
    ],

    'plan_enum' => Coollabsio\LaravelSaas\Enums\Plan::class,

    'stripe' => [
        'prices' => [
            'pro' => [
                'monthly' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            ],
            'enterprise' => [
                'monthly' => env('STRIPE_ENTERPRISE_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_ENTERPRISE_YEARLY_PRICE_ID'),
            ],
        ],
        'dynamic_price_id' => env('STRIPE_DYNAMIC_PRICE_ID'),
    ],

    'routes' => [
        'teams' => true,
        'billing' => true,
        'instance' => true,
    ],
];

<?php

use Coollabsio\LaravelSaas\Support\StripePrices;
use Illuminate\Support\Facades\Cache;

it('fetches and caches stripe prices with savings', function () {
    config([
        'saas.stripe.prices.pro.monthly' => 'price_1SyCgnDTL8bDimfEbq9Zz5bV',
        'saas.stripe.prices.pro.yearly' => 'price_1SyChIDTL8bDimfEFzah5rmJ',
        'saas.stripe.prices.enterprise.monthly' => null,
        'saas.stripe.prices.enterprise.yearly' => null,
    ]);

    Cache::forget(StripePrices::CACHE_KEY);

    $prices = StripePrices::all();

    expect($prices)->toHaveKeys(['pro', 'enterprise'])
        ->and($prices['pro']['monthly'])->toHaveKeys(['amount', 'formatted'])
        ->and($prices['pro']['monthly']['amount'])->toBeInt()->toBeGreaterThan(0)
        ->and($prices['pro']['monthly']['formatted'])->toBeString()
        ->and($prices['pro']['yearly'])->toHaveKeys(['amount', 'formatted'])
        ->and($prices['pro']['yearlySavingsPercent'])->toBeInt()
        ->and($prices['enterprise']['monthly'])->toBeNull()
        ->and($prices['enterprise']['yearly'])->toBeNull()
        ->and($prices['enterprise']['yearlySavingsPercent'])->toBeNull();

    expect(Cache::has(StripePrices::CACHE_KEY))->toBeTrue();
});

it('clears cache', function () {
    Cache::put(StripePrices::CACHE_KEY, ['test'], now()->addMonth());

    StripePrices::clearCache();

    expect(Cache::has(StripePrices::CACHE_KEY))->toBeFalse();
});

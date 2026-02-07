<?php

use Coollabsio\LaravelSaas\Support\StripePrices;
use Illuminate\Support\Facades\Cache;

it('clears stripe price cache via artisan command', function () {
    Cache::put(StripePrices::CACHE_KEY, ['test'], now()->addMonth());

    $this->artisan('billing:clear-price-cache')
        ->expectsOutput('Stripe price cache cleared.')
        ->assertSuccessful();

    expect(Cache::has(StripePrices::CACHE_KEY))->toBeFalse();
});

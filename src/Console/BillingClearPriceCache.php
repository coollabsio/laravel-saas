<?php

namespace Coollabsio\LaravelSaas\Console;

use Coollabsio\LaravelSaas\Support\StripePrices;
use Illuminate\Console\Command;

class BillingClearPriceCache extends Command
{
    protected $signature = 'billing:clear-price-cache';

    protected $description = 'Clear the cached Stripe price data';

    public function handle(): void
    {
        StripePrices::clearCache();

        $this->info('Stripe price cache cleared.');
    }
}

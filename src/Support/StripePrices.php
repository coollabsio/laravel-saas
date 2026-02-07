<?php

namespace Coollabsio\LaravelSaas\Support;

use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;

class StripePrices
{
    public const string CACHE_KEY = 'stripe:prices';

    /**
     * Get prices for all paid plans and intervals.
     *
     * @return array<string, array{monthly: array{amount: int, formatted: string}|null, yearly: array{amount: int, formatted: string}|null, yearlySavingsPercent: int|null}>
     */
    public static function all(): array
    {
        $planEnum = Billing::planEnum();

        return Cache::remember(static::CACHE_KEY, now()->addMonth(), function () use ($planEnum) {
            $prices = [];

            foreach ($planEnum::paid() as $plan) {
                $monthly = static::fetch($plan->stripePriceId('monthly'));
                $yearly = static::fetch($plan->stripePriceId('yearly'));

                $savingsPercent = null;
                if ($monthly && $yearly) {
                    $annualFromMonthly = $monthly['amount'] * 12;
                    $savingsPercent = (int) round(($annualFromMonthly - $yearly['amount']) / $annualFromMonthly * 100);
                }

                $prices[$plan->value] = [
                    'monthly' => $monthly,
                    'yearly' => $yearly,
                    'yearlySavingsPercent' => $savingsPercent,
                ];
            }

            return $prices;
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Fetch a single Stripe price.
     *
     * @return array{amount: int, formatted: string}|null
     */
    private static function fetch(?string $priceId): ?array
    {
        if (blank($priceId)) {
            return null;
        }

        $price = Cashier::stripe()->prices->retrieve($priceId);

        $amount = $price->unit_amount;
        $currency = strtoupper($price->currency);

        return [
            'amount' => $amount,
            'formatted' => number_format($amount / 100, 2).' '.$currency,
        ];
    }
}

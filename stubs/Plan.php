<?php

namespace App\Enums;

use Coollabsio\LaravelSaas\Contracts\PlanContract;

enum Plan: string implements PlanContract
{
    case Free = 'free';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    /**
     * @return list<self>
     */
    public static function paid(): array
    {
        return [self::Pro, self::Enterprise];
    }

    public function stripePriceId(string $interval = 'monthly'): ?string
    {
        return match ($this) {
            self::Free => null,
            self::Pro => config("saas.stripe.prices.pro.{$interval}"),
            self::Enterprise => config("saas.stripe.prices.enterprise.{$interval}"),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    public function tier(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Pro => 1,
            self::Enterprise => 2,
        };
    }

    public static function fromPriceId(string $priceId): ?self
    {
        foreach (self::paid() as $plan) {
            foreach (['monthly', 'yearly'] as $interval) {
                if ($plan->stripePriceId($interval) === $priceId) {
                    return $plan;
                }
            }
        }

        return null;
    }
}

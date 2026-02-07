<?php

namespace Coollabsio\LaravelSaas\Concerns;

use Coollabsio\LaravelSaas\Contracts\PlanContract;
use Coollabsio\LaravelSaas\Support\Billing;
use Laravel\Cashier\Billable;

trait HasBilling
{
    use Billable;

    public function plan(): PlanContract
    {
        $planEnum = Billing::planEnum();

        if (! Billing::enabled()) {
            return $planEnum::from('enterprise');
        }

        if (Billing::isDynamic()) {
            return $planEnum::from('free');
        }

        if (! $this->subscribed()) {
            return $planEnum::from('free');
        }

        foreach ($planEnum::paid() as $plan) {
            $priceId = $plan->stripePriceId();

            if ($priceId && $this->subscribedToPrice($priceId)) {
                return $plan;
            }
        }

        return $planEnum::from('free');
    }

    public function onPlan(string|PlanContract $plan): bool
    {
        if (is_string($plan)) {
            $planEnum = Billing::planEnum();
            $plan = $planEnum::from($plan);
        }

        return $this->plan() === $plan;
    }

    public function canAccess(string|PlanContract $plan): bool
    {
        if (is_string($plan)) {
            $planEnum = Billing::planEnum();
            $plan = $planEnum::from($plan);
        }

        return $this->plan()->tier() >= $plan->tier();
    }

    public function hasActiveDynamicSubscription(): bool
    {
        return Billing::isDynamic() && $this->subscribed('default');
    }

    public function dynamicQuantity(): int
    {
        return $this->subscription('default')?->quantity ?? 0;
    }

    public function updateDynamicQuantity(int $quantity): void
    {
        $subscription = $this->subscription('default');

        if ($subscription) {
            $subscription->updateQuantity($quantity);
        } else {
            $this->newSubscription('default', Billing::dynamicPriceId())
                ->create();

            $this->subscription('default')->updateQuantity($quantity);
        }
    }

    public function reportUsage(string $meterEventName, int $quantity = 1): void
    {
        $this->reportMeterEvent($meterEventName, $quantity);
    }
}

<?php

namespace Coollabsio\LaravelSaas\Support;

class Billing
{
    public static function enabled(): bool
    {
        return ! config('saas.self_hosted');
    }

    public static function isDynamic(): bool
    {
        return static::enabled() && filled(config('saas.stripe.dynamic_price_id'));
    }

    public static function isTiered(): bool
    {
        return static::enabled() && ! static::isDynamic();
    }

    public static function requiresSubscription(): bool
    {
        return static::enabled() && config('saas.require_subscription');
    }

    public static function dynamicPriceId(): ?string
    {
        return config('saas.stripe.dynamic_price_id');
    }

    /**
     * @return 'tiered'|'dynamic'|null
     */
    public static function mode(): ?string
    {
        if (! static::enabled()) {
            return null;
        }

        return static::isDynamic() ? 'dynamic' : 'tiered';
    }

    /**
     * Resolve the configured plan enum class.
     */
    public static function planEnum(): string
    {
        return config('saas.plan_enum');
    }

    /**
     * Resolve the configured team model class.
     */
    public static function teamModel(): string
    {
        return config('saas.models.team');
    }

    /**
     * Resolve the configured team invitation model class.
     */
    public static function teamInvitationModel(): string
    {
        return config('saas.models.team_invitation');
    }

    /**
     * Resolve the configured user model class.
     */
    public static function userModel(): string
    {
        return config('saas.models.user');
    }
}

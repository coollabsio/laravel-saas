<?php

use Coollabsio\LaravelSaas\Enums\Plan;
use App\Models\Team;

it('returns enterprise plan when self-hosted', function () {
    config(['saas.self_hosted' => true]);

    $team = Team::factory()->create();

    expect($team->plan())->toBe(Plan::Enterprise);
});

it('returns free plan when cloud with no subscription', function () {
    config(['saas.self_hosted' => false]);

    $team = Team::factory()->create();

    expect($team->plan())->toBe(Plan::Free);
});

it('checks plan hierarchy with canAccess', function () {
    config(['saas.self_hosted' => false]);

    $team = Team::factory()->create();

    expect($team->canAccess(Plan::Free))->toBeTrue()
        ->and($team->canAccess(Plan::Pro))->toBeFalse()
        ->and($team->canAccess('free'))->toBeTrue();
});

it('self-hosted team can access all plans', function () {
    config(['saas.self_hosted' => true]);

    $team = Team::factory()->create();

    expect($team->canAccess(Plan::Free))->toBeTrue()
        ->and($team->canAccess(Plan::Pro))->toBeTrue()
        ->and($team->canAccess(Plan::Enterprise))->toBeTrue();
});

it('checks exact plan match with onPlan', function () {
    config(['saas.self_hosted' => true]);

    $team = Team::factory()->create();

    expect($team->onPlan(Plan::Enterprise))->toBeTrue()
        ->and($team->onPlan('enterprise'))->toBeTrue()
        ->and($team->onPlan(Plan::Free))->toBeFalse();
});

it('resolves plan from stripe price id', function () {
    config([
        'saas.stripe.prices.pro.monthly' => 'price_pro_monthly',
        'saas.stripe.prices.pro.yearly' => 'price_pro_yearly',
        'saas.stripe.prices.enterprise.monthly' => 'price_enterprise_monthly',
        'saas.stripe.prices.enterprise.yearly' => 'price_enterprise_yearly',
    ]);

    expect(Plan::fromPriceId('price_pro_monthly'))->toBe(Plan::Pro)
        ->and(Plan::fromPriceId('price_pro_yearly'))->toBe(Plan::Pro)
        ->and(Plan::fromPriceId('price_enterprise_monthly'))->toBe(Plan::Enterprise)
        ->and(Plan::fromPriceId('price_enterprise_yearly'))->toBe(Plan::Enterprise)
        ->and(Plan::fromPriceId('price_unknown'))->toBeNull();
});

it('returns correct price id for interval', function () {
    config([
        'saas.stripe.prices.pro.monthly' => 'price_pro_monthly',
        'saas.stripe.prices.pro.yearly' => 'price_pro_yearly',
        'saas.stripe.prices.enterprise.monthly' => 'price_ent_monthly',
        'saas.stripe.prices.enterprise.yearly' => 'price_ent_yearly',
    ]);

    expect(Plan::Pro->stripePriceId('monthly'))->toBe('price_pro_monthly')
        ->and(Plan::Pro->stripePriceId('yearly'))->toBe('price_pro_yearly')
        ->and(Plan::Enterprise->stripePriceId('monthly'))->toBe('price_ent_monthly')
        ->and(Plan::Enterprise->stripePriceId('yearly'))->toBe('price_ent_yearly')
        ->and(Plan::Free->stripePriceId('monthly'))->toBeNull();
});

it('defaults to monthly interval', function () {
    config([
        'saas.stripe.prices.pro.monthly' => 'price_pro_monthly',
    ]);

    expect(Plan::Pro->stripePriceId())->toBe('price_pro_monthly');
});

it('returns free plan in dynamic billing mode', function () {
    config([
        'saas.self_hosted' => false,
        'saas.stripe.dynamic_price_id' => 'price_dynamic_test',
    ]);

    $team = Team::factory()->create();

    expect($team->plan())->toBe(Plan::Free);
});

it('has correct tier ordering', function () {
    expect(Plan::Free->tier())->toBeLessThan(Plan::Pro->tier())
        ->and(Plan::Pro->tier())->toBeLessThan(Plan::Enterprise->tier());
});

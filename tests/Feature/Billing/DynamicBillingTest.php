<?php

use Coollabsio\LaravelSaas\Enums\Plan;
use Coollabsio\LaravelSaas\Http\Middleware\EnsurePlanAccess;
use App\Models\Team;
use App\Models\User;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\Request;

beforeEach(function () {
    config(['saas.self_hosted' => false]);
});

it('reports dynamic mode when dynamic price id is set', function () {
    config(['saas.stripe.dynamic_price_id' => 'price_dynamic_test']);

    expect(Billing::isDynamic())->toBeTrue()
        ->and(Billing::isTiered())->toBeFalse()
        ->and(Billing::mode())->toBe('dynamic');
});

it('reports tiered mode when dynamic price id is not set', function () {
    config(['saas.stripe.dynamic_price_id' => null]);

    expect(Billing::isDynamic())->toBeFalse()
        ->and(Billing::isTiered())->toBeTrue()
        ->and(Billing::mode())->toBe('tiered');
});

it('reports no mode when billing is disabled', function () {
    config(['saas.self_hosted' => true]);

    expect(Billing::isDynamic())->toBeFalse()
        ->and(Billing::isTiered())->toBeFalse()
        ->and(Billing::mode())->toBeNull();
});

it('returns free plan in dynamic mode', function () {
    config(['saas.stripe.dynamic_price_id' => 'price_dynamic_test']);

    $team = Team::factory()->create();

    expect($team->plan())->toBe(Plan::Free);
});

it('passes EnsurePlanAccess middleware in dynamic mode', function () {
    config(['saas.stripe.dynamic_price_id' => 'price_dynamic_test']);

    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team, ['role' => 'owner']);
    $user->switchTeam($team);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsurePlanAccess;
    $response = $middleware->handle($request, fn ($req) => response('ok'), 'enterprise');

    expect($response->getContent())->toBe('ok');
});

it('renders billing page in dynamic mode', function () {
    config(['saas.stripe.dynamic_price_id' => 'price_dynamic_test']);

    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team, ['role' => 'owner']);
    $user->switchTeam($team);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/settings/billing')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Billing')
            ->where('billingMode', 'dynamic')
        );
});

it('returns dynamic price id from config', function () {
    config(['saas.stripe.dynamic_price_id' => 'price_dynamic_test']);

    expect(Billing::dynamicPriceId())->toBe('price_dynamic_test');
});

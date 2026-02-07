<?php

use Coollabsio\LaravelSaas\Http\Middleware\EnsureSubscribed;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->user->switchTeam($this->team);
});

it('passes when require_subscription is false', function () {
    config(['saas.self_hosted' => false, 'saas.require_subscription' => false]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsureSubscribed;
    $response = $middleware->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('passes in self-hosted mode', function () {
    config(['saas.self_hosted' => true, 'saas.require_subscription' => true]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsureSubscribed;
    $response = $middleware->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('redirects unsubscribed user to billing when require_subscription is true', function () {
    config(['saas.self_hosted' => false, 'saas.require_subscription' => true]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsureSubscribed;
    $response = $middleware->handle($request, fn ($req) => response('ok'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('billing.index'));
});

it('passes subscribed user through when require_subscription is true', function () {
    config(['saas.self_hosted' => false, 'saas.require_subscription' => true]);

    $this->team->forceFill([
        'stripe_id' => 'cus_test',
    ])->save();

    $this->team->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
    ]);

    $this->user->setRelation('currentTeam', $this->team->fresh());

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsureSubscribed;
    $response = $middleware->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});

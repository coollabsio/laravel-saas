<?php

use Coollabsio\LaravelSaas\Http\Middleware\EnsurePlanAccess;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->user->switchTeam($this->team);
});

it('always passes in self-hosted mode', function () {
    config(['saas.self_hosted' => true]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsurePlanAccess;
    $response = $middleware->handle($request, fn ($req) => response('ok'), 'pro');

    expect($response->getContent())->toBe('ok');
});

it('blocks access when team lacks required plan', function () {
    config(['saas.self_hosted' => false]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsurePlanAccess;

    $middleware->handle($request, fn ($req) => response('ok'), 'pro');
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

it('allows access when team has sufficient plan in self-hosted', function () {
    config(['saas.self_hosted' => true]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $middleware = new EnsurePlanAccess;
    $response = $middleware->handle($request, fn ($req) => response('ok'), 'enterprise');

    expect($response->getContent())->toBe('ok');
});

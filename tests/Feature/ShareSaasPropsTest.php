<?php

use Coollabsio\LaravelSaas\Http\Middleware\ShareSaasProps;
use Illuminate\Http\Request;
use Inertia\Inertia;

it('shares dev credentials when app is in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $middleware = new ShareSaasProps;
    $request = Request::create('/');

    $middleware->handle($request, function () {
        return response('ok');
    });

    $shared = Inertia::getShared();
    $dev = value($shared['dev']);

    expect($dev)->toBe([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('does not share dev credentials in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $middleware = new ShareSaasProps;
    $request = Request::create('/');

    $middleware->handle($request, function () {
        return response('ok');
    });

    $shared = Inertia::getShared();
    $dev = value($shared['dev']);

    expect($dev)->toBeNull();
});

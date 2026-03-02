<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Coollabsio\LaravelSaas\Support\Billing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    /**
     * Routes that unsubscribed users may still access.
     *
     * @var array<int, string>
     */
    protected array $allowedRoutes = [
        'billing.*',
        'login',
        'register',
        'logout',
        'password.*',
        'verification.*',
        'two-factor.*',
        'user-password.*',
        'user-profile-information.*',
        'cashier.webhook',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Billing::requiresSubscription()) {
            return $next($request);
        }

        if (! $request->user()) {
            return $next($request);
        }

        if ($request->routeIs($this->allowedRoutes)) {
            return $next($request);
        }

        if ($request->user()->isRootUser()) {
            return $next($request);
        }

        if ($request->user()->currentTeam?->subscribed()) {
            return $next($request);
        }

        return redirect()->route('billing.index');
    }
}

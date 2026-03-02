<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Coollabsio\LaravelSaas\Support\Billing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    /**
     * URL path prefixes that unsubscribed users may still access.
     *
     * @var array<int, string>
     */
    protected array $allowedPaths = [
        'settings/*',
        'user/*',
        'login',
        'register',
        'logout',
        'forgot-password',
        'reset-password/*',
        'email/verify/*',
        'stripe/*',
        'billing/*',
        'teams/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Billing::requiresSubscription()) {
            return $next($request);
        }

        if (! $request->user()) {
            return $next($request);
        }

        if ($request->is($this->allowedPaths)) {
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

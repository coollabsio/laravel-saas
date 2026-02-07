<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Coollabsio\LaravelSaas\Support\Billing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Billing::requiresSubscription()) {
            return $next($request);
        }

        if ($request->user()?->currentTeam?->subscribed()) {
            return $next($request);
        }

        return redirect()->route('billing.index');
    }
}

<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Coollabsio\LaravelSaas\Support\Billing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanAccess
{
    public function handle(Request $request, Closure $next, string $plan): Response
    {
        if (! Billing::enabled() || Billing::isDynamic()) {
            return $next($request);
        }

        $planEnum = Billing::planEnum();
        $requiredPlan = $planEnum::from($plan);
        $team = $request->user()?->currentTeam;

        if (! $team || ! $team->canAccess($requiredPlan)) {
            abort(403, 'Your current plan does not include access to this feature.');
        }

        return $next($request);
    }
}

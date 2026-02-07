<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Closure;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('saas.self_hosted')) {
            return $next($request);
        }

        $instanceSettingsModel = Billing::instanceSettingsModel();

        if ($instanceSettingsModel::registrationEnabled()) {
            return $next($request);
        }

        if ($request->is('register') || $request->is('*/register')) {
            return redirect('/login')->with('status', 'Registration is currently disabled.');
        }

        return $next($request);
    }
}

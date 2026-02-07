<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRootUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('saas.self_hosted') || ! $request->user()?->isRootUser()) {
            abort(403);
        }

        return $next($request);
    }
}

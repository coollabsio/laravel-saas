<?php

namespace Coollabsio\LaravelSaas\Http\Middleware;

use Closure;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Coollabsio\LaravelSaas\Models\InstanceSettings;
use Symfony\Component\HttpFoundation\Response;

class ShareSaasProps
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'currentTeam' => fn () => $request->user()?->currentTeam,
            'teams' => fn () => $request->user()?->teams()->get()->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => $team->personal_team,
                'role' => $team->pivot->role,
            ]),
            'billing' => fn () => [
                'enabled' => Billing::enabled(),
                'mode' => Billing::mode(),
                'currentPlan' => $request->user()?->currentTeam?->plan()->value,
                'requiresSubscription' => Billing::requiresSubscription(),
            ],
            'instance' => fn () => [
                'selfHosted' => config('saas.self_hosted'),
                'isRootUser' => $request->user()?->isRootUser() ?? false,
                'registrationEnabled' => Billing::instanceSettingsModel()::registrationEnabled(),
            ],
        ]);

        return $next($request);
    }
}

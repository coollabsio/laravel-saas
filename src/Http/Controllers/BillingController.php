<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Http\Requests\CheckoutRequest;
use Coollabsio\LaravelSaas\Support\Billing;
use Coollabsio\LaravelSaas\Support\StripePrices;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(Billing::enabled(), 404);

        $team = $request->user()->currentTeam;
        $planEnum = Billing::planEnum();

        $availablePlans = collect($planEnum::paid())
            ->filter(fn ($plan) => filled($plan->stripePriceId()))
            ->map(fn ($plan) => [
                'value' => $plan->value,
                'label' => $plan->label(),
            ])
            ->values()
            ->all();

        return Inertia::render('settings/Billing', [
            'plan' => $team->plan()->value,
            'planLabel' => $team->plan()->label(),
            'subscribed' => $team->subscribed(),
            'billingMode' => Billing::mode(),
            'dynamicQuantity' => Billing::isDynamic() ? $team->dynamicQuantity() : null,
            'availablePlans' => $availablePlans,
            'prices' => Billing::isTiered() ? StripePrices::all() : null,
        ]);
    }

    public function checkout(CheckoutRequest $request): mixed
    {
        abort_unless(Billing::enabled(), 404);

        $team = $request->user()->currentTeam;

        if (Billing::isDynamic()) {
            $checkout = $team->newSubscription('default', Billing::dynamicPriceId())
                ->checkout([
                    'success_url' => route('billing.index'),
                    'cancel_url' => route('billing.index'),
                ]);

            return Inertia::location($checkout->url);
        }

        $plan = $request->plan();

        $checkout = $team->newSubscription('default', $plan->stripePriceId($request->interval()))
            ->checkout([
                'success_url' => route('billing.index'),
                'cancel_url' => route('billing.index'),
            ]);

        return Inertia::location($checkout->url);
    }

    public function portal(Request $request): mixed
    {
        abort_unless(Billing::enabled(), 404);

        return Inertia::location(
            $request->user()->currentTeam->billingPortalUrl(route('billing.index'))
        );
    }
}

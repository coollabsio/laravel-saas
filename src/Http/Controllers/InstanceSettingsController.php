<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class InstanceSettingsController extends Controller
{
    public function edit(): Response
    {
        $instanceSettingsModel = Billing::instanceSettingsModel();

        return Inertia::render('settings/Instance', [
            'settings' => $instanceSettingsModel::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_enabled' => ['required', 'boolean'],
        ]);

        $instanceSettingsModel = Billing::instanceSettingsModel();
        $instanceSettingsModel::current()->update($validated);

        return back();
    }
}

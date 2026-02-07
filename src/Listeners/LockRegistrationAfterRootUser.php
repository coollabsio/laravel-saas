<?php

namespace Coollabsio\LaravelSaas\Listeners;

use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Auth\Events\Registered;

class LockRegistrationAfterRootUser
{
    public function handle(Registered $event): void
    {
        if (! config('saas.self_hosted')) {
            return;
        }

        $userModel = Billing::userModel();

        if ($userModel::count() !== 1) {
            return;
        }

        $instanceSettingsModel = Billing::instanceSettingsModel();
        $instanceSettingsModel::current()->update(['registration_enabled' => false]);
    }
}

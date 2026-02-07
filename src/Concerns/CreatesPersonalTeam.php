<?php

namespace Coollabsio\LaravelSaas\Concerns;

use Coollabsio\LaravelSaas\Support\Billing;

trait CreatesPersonalTeam
{
    /**
     * Create a personal team for the given user and switch to it.
     */
    protected function createPersonalTeam($user): void
    {
        $teamModel = Billing::teamModel();

        $team = $teamModel::forceCreate([
            'name' => $user->name."'s Team",
            'personal_team' => true,
            'owner_id' => $user->id,
        ]);

        $team->users()->attach($user, ['role' => 'owner']);

        $user->switchTeam($team);
    }
}

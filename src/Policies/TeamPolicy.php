<?php

namespace Coollabsio\LaravelSaas\Policies;

use Illuminate\Database\Eloquent\Model;

class TeamPolicy
{
    public function update(Model $user, Model $team): bool
    {
        return $team->isOwner($user);
    }

    public function delete(Model $user, Model $team): bool
    {
        return $team->isOwner($user) && ! $team->isPersonalTeam();
    }

    public function manageMembers(Model $user, Model $team): bool
    {
        return $team->isOwner($user);
    }
}

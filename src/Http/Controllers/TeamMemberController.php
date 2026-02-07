<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    public function destroy($team, $user): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $userModel = Billing::userModel();

        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);
        $user = $userModel::findOrFail($user instanceof \Illuminate\Database\Eloquent\Model ? $user->id : $user);

        Gate::authorize('manageMembers', $team);

        abort_if($team->isOwner($user), 403, 'Cannot remove the team owner.');

        $team->users()->detach($user);

        if ($user->current_team_id === $team->id) {
            $personalTeam = $user->ownedTeams()->where('personal_team', true)->first();
            $user->switchTeam($personalTeam);
        }

        return to_route('teams.edit');
    }
}

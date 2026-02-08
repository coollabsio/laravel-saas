<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Enums\TeamRole;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class TeamMemberController extends Controller
{
    public function update(Request $request, $team, $user): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $userModel = Billing::userModel();

        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);
        $user = $userModel::findOrFail($user instanceof \Illuminate\Database\Eloquent\Model ? $user->id : $user);

        Gate::authorize('manageMembers', $team);

        $validated = $request->validate([
            'role' => ['required', new Enum(TeamRole::class)],
        ]);

        abort_if($team->isOwner($user), 403, 'Cannot change the role of the team owner.');

        $team->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back();
    }

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

<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Http\Requests\StoreTeamRequest;
use Coollabsio\LaravelSaas\Http\Requests\UpdateTeamRequest;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $teamModel = Billing::teamModel();

        $team = $teamModel::create([
            'name' => $request->validated('name'),
            'owner_id' => $request->user()->id,
            'personal_team' => false,
        ]);

        $team->users()->attach($request->user(), ['role' => 'owner']);

        $request->user()->switchTeam($team);

        return to_route('teams.edit');
    }

    public function edit(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('settings/Team', [
            'team' => $team,
            'members' => $team->users()->get()->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]),
            'invitations' => $team->invitations()->get(),
            'isOwner' => $team->isOwner($request->user()),
        ]);
    }

    public function update(UpdateTeamRequest $request, $team): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);

        Gate::authorize('update', $team);

        $team->update($request->validated());

        return to_route('teams.edit');
    }

    public function destroy(Request $request, $team): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);

        Gate::authorize('delete', $team);

        $personalTeam = $request->user()->ownedTeams()->where('personal_team', true)->first();

        $team->delete();

        $request->user()->switchTeam($personalTeam);

        return to_route('home');
    }

    public function switchTeam(Request $request, $team): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);

        abort_unless($team->hasUser($request->user()), 403);

        $request->user()->switchTeam($team);

        return back();
    }
}

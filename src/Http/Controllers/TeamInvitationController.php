<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Http\Requests\StoreTeamInvitationRequest;
use Coollabsio\LaravelSaas\Mail\TeamInvitationMail;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    public function store(StoreTeamInvitationRequest $request, $team): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);

        Gate::authorize('manageMembers', $team);

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'token' => Str::random(40),
        ]);

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

        return to_route('teams.edit');
    }

    public function destroy($team, $invitation): RedirectResponse
    {
        $teamModel = Billing::teamModel();
        $invitationModel = Billing::teamInvitationModel();

        $team = $teamModel::findOrFail($team instanceof \Illuminate\Database\Eloquent\Model ? $team->id : $team);
        $invitation = $invitationModel::findOrFail($invitation instanceof \Illuminate\Database\Eloquent\Model ? $invitation->id : $invitation);

        Gate::authorize('manageMembers', $team);

        $invitation->delete();

        return to_route('teams.edit');
    }

    public function accept(Request $request, string $token): Response|RedirectResponse
    {
        $invitationModel = Billing::teamInvitationModel();
        $invitation = $invitationModel::where('token', $token)->firstOrFail();

        return Inertia::render('TeamInvitation', [
            'invitation' => $invitation->load('team'),
        ]);
    }

    public function process(Request $request, string $token): RedirectResponse
    {
        $invitationModel = Billing::teamInvitationModel();
        $invitation = $invitationModel::where('token', $token)->firstOrFail();
        $user = $request->user();

        abort_unless($user, 403, 'You must be logged in to accept an invitation.');
        abort_if($invitation->team->hasUser($user), 409, 'You are already a member of this team.');

        $invitation->team->users()->attach($user, ['role' => $invitation->role]);

        $user->switchTeam($invitation->team);

        $invitation->delete();

        return to_route('home');
    }
}

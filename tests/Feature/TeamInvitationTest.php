<?php

use Coollabsio\LaravelSaas\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('team owner can send an invitation', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $this->actingAs($owner)
        ->post(route('team-invitations.store', $team), [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])
        ->assertRedirect(route('teams.edit'));

    Mail::assertSent(TeamInvitationMail::class, function ($mail) {
        return $mail->hasTo('invitee@example.com');
    });

    expect($team->invitations()->count())->toBe(1);
});

test('non-owner cannot send an invitation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->post(route('team-invitations.store', $team), [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});

test('cannot invite existing team member', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);

    $this->actingAs($owner)
        ->post(route('team-invitations.store', $team), [
            'email' => $member->email,
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

test('cannot send duplicate invitation', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $team->invitations()->create([
        'email' => 'invitee@example.com',
        'role' => 'member',
        'token' => 'test-token',
    ]);

    $this->actingAs($owner)
        ->post(route('team-invitations.store', $team), [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

test('authenticated user can accept an invitation', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => $invitee->email,
        'role' => 'member',
        'token' => 'accept-token',
    ]);

    $this->actingAs($invitee)
        ->post(route('team-invitations.process', 'accept-token'))
        ->assertRedirect(route('home'));

    expect($team->hasUser($invitee))->toBeTrue()
        ->and(TeamInvitation::find($invitation->id))->toBeNull()
        ->and($invitee->fresh()->current_team_id)->toBe($team->id);
});

test('invitation accept page is viewable', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $team->invitations()->create([
        'email' => 'guest@example.com',
        'role' => 'member',
        'token' => 'view-token',
    ]);

    $this->get(route('team-invitations.accept', 'view-token'))
        ->assertSuccessful();
});

test('team owner can cancel an invitation', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'cancel@example.com',
        'role' => 'member',
        'token' => 'cancel-token',
    ]);

    $this->actingAs($owner)
        ->delete(route('team-invitations.destroy', [$team, $invitation]))
        ->assertRedirect(route('teams.edit'));

    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

test('team invitation email is plain text', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'invitee@example.com',
        'role' => 'member',
        'token' => 'plain-text-token',
    ]);

    $mail = new TeamInvitationMail($invitation);
    $rendered = $mail->render();

    expect($rendered)->toContain(e($team->name))
        ->and($rendered)->toContain(route('team-invitations.accept', 'plain-text-token'))
        ->and($rendered)->not->toContain('<x-mail::')
        ->and($rendered)->not->toContain('<x-mail::button');
});

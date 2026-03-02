<?php

use App\Models\TeamInvitation;
use App\Models\User;
use Coollabsio\LaravelSaas\Mail\TeamInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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
    ]);

    $processUrl = URL::temporarySignedRoute(
        'team-invitations.process',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $this->actingAs($invitee)
        ->post($processUrl)
        ->assertRedirect(route('home'));

    expect($team->hasUser($invitee))->toBeTrue()
        ->and(TeamInvitation::find($invitation->id))->toBeNull()
        ->and($invitee->fresh()->current_team_id)->toBe($team->id);
});

test('invitation accept page is viewable with valid signature', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'team-invitations.accept',
        now()->addDays(7),
        ['invitation' => $invitation->id]
    );

    $this->get($acceptUrl)->assertSuccessful();
});

test('invitation accept page rejects invalid signature', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);

    $this->get(route('team-invitations.accept', ['invitation' => $invitation->id]))
        ->assertForbidden();
});

test('invitation accept page rejects expired signature', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);

    $expiredUrl = URL::temporarySignedRoute(
        'team-invitations.accept',
        now()->subMinute(),
        ['invitation' => $invitation->id]
    );

    $this->get($expiredUrl)->assertForbidden();
});

test('team owner can cancel an invitation', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'cancel@example.com',
        'role' => 'member',
    ]);

    $this->actingAs($owner)
        ->delete(route('team-invitations.destroy', [$team, $invitation]))
        ->assertRedirect(route('teams.edit'));

    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

test('team invitation email contains signed URL', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $invitation = $team->invitations()->create([
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $mail = new TeamInvitationMail($invitation);
    $rendered = $mail->render();

    expect($rendered)->toContain(e($team->name))
        ->and($rendered)->toContain('signature=')
        ->and($rendered)->toContain('expires=')
        ->and($rendered)->toContain('/invitations/'.$invitation->id)
        ->and($rendered)->not->toContain('<x-mail::')
        ->and($rendered)->not->toContain('<x-mail::button');
});

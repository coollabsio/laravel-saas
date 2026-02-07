<?php

use App\Models\Team;
use App\Models\User;

test('personal team is created on registration', function () {
    $user = User::factory()->create();

    expect($user->currentTeam)->not->toBeNull()
        ->and($user->currentTeam->personal_team)->toBeTrue()
        ->and($user->currentTeam->isOwner($user))->toBeTrue();
});

test('user can create a team', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.store'), ['name' => 'New Team'])
        ->assertRedirect(route('teams.edit'));

    expect($user->fresh()->currentTeam->name)->toBe('New Team')
        ->and($user->fresh()->currentTeam->personal_team)->toBeFalse();
});

test('user can switch teams', function () {
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Second Team',
        'owner_id' => $user->id,
        'personal_team' => false,
    ]);
    $team->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user)
        ->put(route('teams.switch', $team))
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($team->id);
});

test('team owner can update team name', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->patch(route('teams.update', $team), ['name' => 'Updated Name'])
        ->assertRedirect(route('teams.edit'));

    expect($team->fresh()->name)->toBe('Updated Name');
});

test('non-owner cannot update team name', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->patch(route('teams.update', $team), ['name' => 'Hacked'])
        ->assertForbidden();
});

test('team owner can delete non-personal team', function () {
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Deletable Team',
        'owner_id' => $user->id,
        'personal_team' => false,
    ]);
    $team->users()->attach($user, ['role' => 'owner']);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->delete(route('teams.destroy', $team))
        ->assertRedirect(route('home'));

    expect(Team::find($team->id))->toBeNull();
});

test('team owner cannot delete personal team', function () {
    $user = User::factory()->create();
    $personalTeam = $user->currentTeam;

    $this->actingAs($user)
        ->delete(route('teams.destroy', $personalTeam))
        ->assertForbidden();
});

test('user cannot switch to a team they do not belong to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)
        ->put(route('teams.switch', $otherUser->currentTeam))
        ->assertForbidden();
});

test('team settings page loads for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('teams.edit'))
        ->assertSuccessful();
});

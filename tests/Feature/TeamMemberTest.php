<?php

use App\Models\User;

test('team owner can remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);

    $this->actingAs($owner)
        ->delete(route('team-members.destroy', [$team, $member]))
        ->assertRedirect(route('teams.edit'));

    expect($team->hasUser($member))->toBeFalse();
});

test('non-owner cannot remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);
    $team->users()->attach($otherMember, ['role' => 'member']);

    $this->actingAs($member)
        ->delete(route('team-members.destroy', [$team, $otherMember]))
        ->assertForbidden();
});

test('owner cannot be removed from team', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $this->actingAs($owner)
        ->delete(route('team-members.destroy', [$team, $owner]))
        ->assertForbidden();
});

test('removed member is switched to their personal team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    $team->users()->attach($member, ['role' => 'member']);
    $member->switchTeam($team);

    $this->actingAs($owner)
        ->delete(route('team-members.destroy', [$team, $member]));

    $member->refresh();
    expect($member->currentTeam->personal_team)->toBeTrue();
});

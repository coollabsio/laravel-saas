<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->user->switchTeam($this->team);
});

it('returns 404 for billing index when self-hosted', function () {
    config(['saas.self_hosted' => true]);

    $this->actingAs($this->user)
        ->get('/settings/billing')
        ->assertNotFound();
});

it('renders billing page when cloud mode', function () {
    config(['saas.self_hosted' => false]);

    $this->withoutVite()
        ->actingAs($this->user)
        ->get('/settings/billing')
        ->assertSuccessful();
});

it('returns 404 for checkout when self-hosted', function () {
    config(['saas.self_hosted' => true]);

    $this->actingAs($this->user)
        ->post('/billing/checkout', ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertNotFound();
});

it('returns 404 for portal when self-hosted', function () {
    config(['saas.self_hosted' => true]);

    $this->actingAs($this->user)
        ->get('/billing/portal')
        ->assertNotFound();
});

it('requires authentication for billing routes', function () {
    $this->get('/settings/billing')->assertRedirect('/login');
    $this->post('/billing/checkout')->assertRedirect('/login');
    $this->get('/billing/portal')->assertRedirect('/login');
});

it('validates checkout plan field', function () {
    config(['saas.self_hosted' => false]);

    $this->actingAs($this->user)
        ->post('/billing/checkout', ['plan' => 'invalid', 'interval' => 'monthly'])
        ->assertSessionHasErrors('plan');
});

it('rejects free plan in checkout', function () {
    config(['saas.self_hosted' => false]);

    $this->actingAs($this->user)
        ->post('/billing/checkout', ['plan' => 'free', 'interval' => 'monthly'])
        ->assertSessionHasErrors('plan');
});

it('validates checkout interval field', function () {
    config(['saas.self_hosted' => false]);

    $this->actingAs($this->user)
        ->post('/billing/checkout', ['plan' => 'pro', 'interval' => 'weekly'])
        ->assertSessionHasErrors('interval');
});

it('requires interval for tiered checkout', function () {
    config(['saas.self_hosted' => false]);

    $this->actingAs($this->user)
        ->post('/billing/checkout', ['plan' => 'pro'])
        ->assertSessionHasErrors('interval');
});

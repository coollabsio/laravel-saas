<?php

use App\Models\User;
use Coollabsio\LaravelSaas\Mail\EmailChangeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('user can request email change', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('email-change.store'), [
            'email' => 'new@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'email-change-sent');

    Mail::assertSent(EmailChangeMail::class, function ($mail) {
        return $mail->hasTo('new@example.com');
    });

    expect($user->fresh()->email)->not->toBe('new@example.com');

    $hash = sha1($user->id.'new@example.com');
    expect(Cache::has("email-change:{$hash}"))->toBeTrue();
});

test('email change requires authentication', function () {
    $this->post(route('email-change.store'), [
        'email' => 'new@example.com',
    ])->assertRedirect(route('login'));
});

test('email change validates email format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('email-change.store'), [
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('email');
});

test('email change validates email uniqueness', function () {
    $user = User::factory()->create();
    $other = User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($user)
        ->post(route('email-change.store'), [
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('same email does not send mail', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'current@example.com']);

    $this->actingAs($user)
        ->post(route('email-change.store'), [
            'email' => 'current@example.com',
        ])
        ->assertRedirect();

    Mail::assertNothingSent();
});

test('signed url updates email', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $hash = sha1($user->id.'new@example.com');

    Cache::put("email-change:{$hash}", 'new@example.com', now()->addMinutes(15));

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->addMinutes(15),
        ['user' => $user->id, 'hash' => $hash],
    );

    $this->get($url)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-success');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Cache::has("email-change:{$hash}"))->toBeFalse();
});

test('expired signed url is rejected', function () {
    $user = User::factory()->create();
    $hash = sha1($user->id.'new@example.com');

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->subMinute(),
        ['user' => $user->id, 'hash' => $hash],
    );

    $this->get($url)->assertForbidden();
});

test('invalid signature is rejected', function () {
    $user = User::factory()->create();

    $this->get(route('email-change.verify', [
        'user' => $user->id,
        'hash' => 'fakehash',
    ]))->assertForbidden();
});

test('email already taken at verify time', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $hash = sha1($user->id.'taken@example.com');

    Cache::put("email-change:{$hash}", 'taken@example.com', now()->addMinutes(15));

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->addMinutes(15),
        ['user' => $user->id, 'hash' => $hash],
    );

    User::factory()->create(['email' => 'taken@example.com']);

    $this->get($url)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-failed');

    expect($user->fresh()->email)->toBe('old@example.com');
});

test('expired cache entry fails gracefully', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $hash = sha1($user->id.'new@example.com');

    // No cache entry — simulates expired cache
    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->addMinutes(15),
        ['user' => $user->id, 'hash' => $hash],
    );

    $this->get($url)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-failed');

    expect($user->fresh()->email)->toBe('old@example.com');
});

test('email change mail is plain text', function () {
    $mail = new EmailChangeMail('https://example.com/verify', 'Test User');
    $rendered = $mail->render();

    expect($rendered)->toContain('https://example.com/verify')
        ->and($rendered)->toContain('Test User')
        ->and($rendered)->toContain('15 minutes')
        ->and($rendered)->not->toContain('<x-mail::')
        ->and($rendered)->not->toContain('<x-mail::button');
});

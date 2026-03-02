<?php

use App\Models\User;
use Coollabsio\LaravelSaas\Mail\EmailChangeMail;
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

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->addMinutes(15),
        ['user' => $user->id, 'email' => 'new@example.com'],
    );

    $this->get($url)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-success');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('expired signed url is rejected', function () {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->subMinute(),
        ['user' => $user->id, 'email' => 'new@example.com'],
    );

    $this->get($url)->assertForbidden();
});

test('invalid signature is rejected', function () {
    $user = User::factory()->create();

    $this->get(route('email-change.verify', [
        'user' => $user->id,
        'email' => 'new@example.com',
    ]))->assertForbidden();
});

test('email already taken at verify time', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $url = URL::temporarySignedRoute(
        'email-change.verify',
        now()->addMinutes(15),
        ['user' => $user->id, 'email' => 'taken@example.com'],
    );

    User::factory()->create(['email' => 'taken@example.com']);

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

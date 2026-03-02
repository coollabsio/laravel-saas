<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Http\Requests\EmailChangeRequest;
use Coollabsio\LaravelSaas\Mail\EmailChangeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailChangeController extends Controller
{
    public function store(EmailChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $newEmail = $request->validated('email');

        if ($newEmail === $user->email) {
            return back();
        }

        $hash = sha1($user->id.$newEmail);

        Cache::put("email-change:{$hash}", $newEmail, now()->addMinutes(15));

        $verifyUrl = URL::temporarySignedRoute(
            'email-change.verify',
            now()->addMinutes(15),
            [
                'user' => $user->id,
                'hash' => $hash,
            ],
        );

        Mail::to($newEmail)->send(
            new EmailChangeMail($verifyUrl, $user->name),
        );

        return back()->with('status', 'email-change-sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userModel = config('saas.models.user');
        $user = $userModel::findOrFail($request->query('user'));
        $hash = $request->query('hash');

        $newEmail = Cache::pull("email-change:{$hash}");

        if (! $newEmail) {
            return redirect()->route('profile.edit')
                ->with('status', 'email-change-failed');
        }

        if ($userModel::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return redirect()->route('profile.edit')
                ->with('status', 'email-change-failed');
        }

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('profile.edit')
            ->with('status', 'email-change-success');
    }
}

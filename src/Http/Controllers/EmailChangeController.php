<?php

namespace Coollabsio\LaravelSaas\Http\Controllers;

use Coollabsio\LaravelSaas\Http\Requests\EmailChangeRequest;
use Coollabsio\LaravelSaas\Mail\EmailChangeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailChangeController extends Controller
{
    public function store(EmailChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($request->validated('email') === $user->email) {
            return back();
        }

        $verifyUrl = URL::temporarySignedRoute(
            'email-change.verify',
            now()->addMinutes(15),
            [
                'user' => $user->id,
                'email' => $request->validated('email'),
            ],
        );

        Mail::to($request->validated('email'))->send(
            new EmailChangeMail($verifyUrl, $user->name),
        );

        return back()->with('status', 'email-change-sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userModel = config('saas.models.user');
        $user = $userModel::findOrFail($request->query('user'));
        $email = $request->query('email');

        if ($userModel::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return redirect()->route('profile.edit')
                ->with('status', 'email-change-failed');
        }

        $user->forceFill([
            'email' => $email,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('profile.edit')
            ->with('status', 'email-change-success');
    }
}

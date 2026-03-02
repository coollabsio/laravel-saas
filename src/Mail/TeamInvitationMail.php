<?php

namespace Coollabsio\LaravelSaas\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Model $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Team Invitation',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'laravel-saas::mail.team-invitation',
            with: [
                'acceptUrl' => URL::temporarySignedRoute(
                    'team-invitations.accept',
                    now()->addDays(7),
                    ['invitation' => $this->invitation->id]
                ),
                'teamName' => $this->invitation->team->name,
            ],
        );
    }
}

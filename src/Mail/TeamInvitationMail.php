<?php

namespace Coollabsio\LaravelSaas\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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
                'acceptUrl' => route('team-invitations.accept', $this->invitation->token),
                'teamName' => $this->invitation->team->name,
            ],
        );
    }
}

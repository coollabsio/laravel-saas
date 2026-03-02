<?php

namespace Coollabsio\LaravelSaas\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $verifyUrl,
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Email Change',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'laravel-saas::mail.email-change',
            with: [
                'verifyUrl' => $this->verifyUrl,
                'userName' => $this->userName,
            ],
        );
    }
}

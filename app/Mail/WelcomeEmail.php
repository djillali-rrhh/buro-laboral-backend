<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public ?string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, ?string $verificationUrl = null)
    {
        $this->userName = $userName;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a ' . config('app.name') . '!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'name' => $this->userName,
                'verificationUrl' => $this->verificationUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $recipientName;
    public string $message;
    public ?string $actionUrl;
    public ?string $actionText;
    public ?string $additionalInfo;

    public function __construct(
        string $title,
        string $recipientName,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $additionalInfo = null
    ) {
        $this->title = $title;
        $this->recipientName = $recipientName;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->additionalInfo = $additionalInfo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.templates.notification',
            with: [
                'title' => $this->title,
                'recipientName' => $this->recipientName,
                'message' => $this->message,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'additionalInfo' => $this->additionalInfo,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
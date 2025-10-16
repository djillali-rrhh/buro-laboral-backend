<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DynamicEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $viewName;
    public $viewData;
    public $emailSubject;
    public $emailAttachments;

    /**
     * Create a new message instance.
     */
    public function __construct(string $view, array $data, string $subject, array $attachments = [])
    {
        $this->viewName = $view;
        $this->viewData = $data;
        $this->emailSubject = $subject;
        $this->emailAttachments = $attachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: $this->viewData
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachmentObjects = [];
        
        foreach ($this->emailAttachments as $file) {
            if (isset($file['path'])) {
                $attachmentObjects[] = Attachment::fromPath($file['path'])
                    ->as($file['name'] ?? basename($file['path']))
                    ->withMime($file['mime'] ?? 'application/octet-stream');
            }
        }
        
        return $attachmentObjects;
    }
}
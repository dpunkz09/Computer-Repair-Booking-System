<?php

namespace App\Mail;

use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $siteName;

    public function __construct(
        public string $emailSubject,
        public string $heading,
        public string $messageText,
        public ?string $actionUrl = null,
        public ?string $actionLabel = 'View ticket',
    ) {
        $this->siteName = SiteSettings::getOrDefault('site_name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-alert',
        );
    }
}

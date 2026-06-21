<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $siteName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->siteName} — SMTP Test",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>This is a test email from <strong>' . e($this->siteName) . '</strong>.</p>'
                . '<p>If you received this message, SMTP is configured correctly and password reset emails should work.</p>',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistOpeningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $clientName,
        public string $lawyerName,
        public string $whenLabel,
        public string $bookingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'A consultation time just opened');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.waitlist-opening');
    }
}

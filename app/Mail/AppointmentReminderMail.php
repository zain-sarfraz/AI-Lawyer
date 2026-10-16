<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $whenLabel,
        public string $lawyerName,
        public string $firmName,
        public string $window,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->window === '1h' ? 'in about an hour' : 'tomorrow';

        return new Envelope(subject: 'Reminder: your appointment '.$label);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.appointment-reminder');
    }
}

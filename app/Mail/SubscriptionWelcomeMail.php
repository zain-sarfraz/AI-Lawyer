<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $planName,
        public readonly string $interval,
        public readonly string $amountLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to AI Lawyer · Your ' . $this->planName . ' plan is active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-welcome',
        );
    }
}

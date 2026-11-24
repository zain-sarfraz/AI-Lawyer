<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\FirmInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FirmLawyerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FirmInvitation $invitation) {}

    public function envelope(): Envelope
    {
        $firmName = $this->invitation->firm?->firm_name ?: 'your firm';

        return new Envelope(
            subject: "You're invited to join {$firmName} on AI Lawyer",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.firm-lawyer-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => route('invitations.accept.show', $this->invitation->token),
            ],
        );
    }
}

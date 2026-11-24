<x-mail::message>
# Join {{ $invitation->firm?->firm_name ?: 'the firm' }} on AI Lawyer

Hello {{ $invitation->name }},

{{ $invitation->inviter?->name ?: 'A firm administrator' }} invited you to join **{{ $invitation->firm?->firm_name ?: 'their firm' }}** as a **{{ $invitation->role }}**.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This link expires on {{ optional($invitation->expires_at)->toDayDateTimeString() ?: 'the expiry date shown in your invite' }}.

If you did not expect this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

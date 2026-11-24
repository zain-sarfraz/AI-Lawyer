<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Domain\Firm\FirmContext;
use App\Mail\FirmLawyerInvitationMail;
use App\Models\Firm;
use App\Models\FirmInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FirmInvitationService
{
    public function __construct(
        private readonly FirmUserService $users,
        private readonly FirmContext $firmContext,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, role?: string}  $data
     */
    public function invite(Firm $firm, User $inviter, array $data): FirmInvitation
    {
        if (! $firm->isFirmAccount()) {
            throw ValidationException::withMessages([
                'email' => 'Lawyer invitations are only available on firm (Growth) accounts.',
            ]);
        }

        if (! $firm->canAddLawyer()) {
            throw ValidationException::withMessages([
                'email' => 'Your plan does not allow additional lawyer seats.',
            ]);
        }

        $email = strtolower(trim($data['email']));
        $role = $data['role'] ?? 'Lawyer';
        if ($role !== 'Lawyer') {
            throw ValidationException::withMessages([
                'role' => 'Only the Lawyer role can be invited to a firm workspace.',
            ]);
        }

        if (User::emailTakenInFirm($email, (int) $firm->id)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used in this firm.',
            ]);
        }

        $existing = FirmInvitation::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('email', $email)
            ->first();

        if ($existing && $existing->status === FirmInvitation::STATUS_PENDING && $existing->isPending()) {
            throw ValidationException::withMessages([
                'email' => 'A pending invitation already exists for this email.',
            ]);
        }

        if ($existing && $existing->status === FirmInvitation::STATUS_ACCEPTED) {
            throw ValidationException::withMessages([
                'email' => 'This email already accepted an invitation for this firm.',
            ]);
        }

        $payload = [
            'firm_id' => $firm->id,
            'invited_by' => $inviter->id,
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'token' => Str::random(64),
            'status' => FirmInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(14),
            'accepted_at' => null,
            'accepted_user_id' => null,
            'email_dispatched_at' => null,
            'email_error' => null,
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            $invitation = $existing;
        } else {
            $invitation = FirmInvitation::query()->create($payload);
        }

        $this->dispatchInvitationEmail($invitation);

        return $invitation->fresh();
    }

    public function revoke(FirmInvitation $invitation): void
    {
        if ($invitation->status !== FirmInvitation::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'invitation' => 'Only pending invitations can be revoked.',
            ]);
        }

        $invitation->forceFill(['status' => FirmInvitation::STATUS_REVOKED])->save();
    }

    /**
     * @param  array{password: string}  $data
     */
    public function accept(FirmInvitation $invitation, array $data): User
    {
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation is no longer valid.',
            ]);
        }

        $firm = Firm::query()->findOrFail($invitation->firm_id);

        if (! $firm->canAddLawyer()) {
            throw ValidationException::withMessages([
                'invitation' => 'This firm has no available lawyer seats.',
            ]);
        }

        if (User::emailTakenInFirm($invitation->email, (int) $firm->id)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used in this firm.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $data, $firm) {
            $user = $this->firmContext->run((int) $firm->id, function () use ($invitation, $data) {
                return $this->users->create([
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'password' => $data['password'],
                    'phone' => $invitation->phone,
                    'title' => 'Lawyer',
                ]);
            });

            $invitation->forceFill([
                'status' => FirmInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ])->save();

            return $user->fresh(['roles', 'lawyer', 'firm']);
        });
    }

    public function dispatchInvitationEmail(FirmInvitation $invitation): void
    {
        $mailer = (string) config('mail.default');

        // Do not pretend inbox delivery when using non-SMTP local drivers.
        if (in_array($mailer, ['log', 'array'], true)) {
            $invitation->forceFill([
                'email_dispatched_at' => null,
                'email_error' => "Mailer [{$mailer}] is configured — invitation email was not sent to an inbox. Use the accept link from the Firm Admin invitations list, or configure SMTP/MAIL_* for real delivery.",
            ])->save();

            return;
        }

        try {
            Mail::to($invitation->email)->send(new FirmLawyerInvitationMail($invitation->loadMissing('firm', 'inviter')));
            $invitation->forceFill([
                'email_dispatched_at' => now(),
                'email_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            $invitation->forceFill([
                'email_error' => $e->getMessage(),
            ])->save();
        }
    }

    public function pendingCount(Firm $firm): int
    {
        return FirmInvitation::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->where('status', FirmInvitation::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}

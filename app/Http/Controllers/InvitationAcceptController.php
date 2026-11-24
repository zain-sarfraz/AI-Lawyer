<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\FirmInvitationService;
use App\Models\FirmInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationAcceptController extends Controller
{
    public function __construct(private readonly FirmInvitationService $invitations) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = FirmInvitation::query()
            ->withoutGlobalScopes()
            ->with('firm')
            ->where('token', $token)
            ->firstOrFail();

        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if (! $invitation->isPending()) {
            return view('invitations.invalid', ['invitation' => $invitation]);
        }

        return view('invitations.accept', ['invitation' => $invitation]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = FirmInvitation::query()
            ->withoutGlobalScopes()
            ->where('token', $token)
            ->firstOrFail();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $this->invitations->accept($invitation, $data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome to '.$user->firm?->firm_name.'. Your lawyer account is active.');
    }
}

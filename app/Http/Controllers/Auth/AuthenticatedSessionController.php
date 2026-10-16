<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $home = $user?->homeRoute() ?? 'dashboard';
        $homeUrl = route($home, absolute: false);

        // Never send Superadmin into a previously intended firm CRM URL (/dashboard, etc.).
        if ($user?->isSuperAdmin()) {
            $request->session()->forget('url.intended');

            return redirect()->to($homeUrl);
        }

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && str_contains($intended, '/superadmin')) {
            return redirect()->to($homeUrl);
        }

        return $intended
            ? redirect()->to($intended)
            : redirect()->to($homeUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

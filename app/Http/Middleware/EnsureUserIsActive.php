<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->isActive()) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        if ($user && ! $user->isSuperAdmin() && $user->firm_id) {
            $status = optional($user->firm)->status ?? 'active';
            if ($status === 'suspended') {
                auth()->logout();

                return redirect()->route('login')->withErrors(['email' => 'This firm workspace is suspended.']);
            }
        }

        return $next($request);
    }
}

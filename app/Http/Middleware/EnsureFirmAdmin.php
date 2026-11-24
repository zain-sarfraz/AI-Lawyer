<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirmAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isFirmAdmin()) {
            abort(403, 'Firm administrator access required.');
        }

        if (! $user->firm || ! $user->firm->isFirmAccount()) {
            abort(403, 'Firm administrator dashboards are only available on firm accounts.');
        }

        return $next($request);
    }
}

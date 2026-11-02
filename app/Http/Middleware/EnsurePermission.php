<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'You do not have permission to perform this action.');
        }

        // Support "a|b" as OR (matches sidebar permission checks).
        $names = str_contains($permission, '|')
            ? explode('|', $permission)
            : [$permission];

        foreach ($names as $name) {
            if ($user->hasPermission(trim($name))) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}

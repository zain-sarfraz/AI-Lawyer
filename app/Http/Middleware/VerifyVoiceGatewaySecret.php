<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyVoiceGatewaySecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.voice_gateway.secret');

        if ($expected === '') {
            // Allow in local when secret is not configured, deny otherwise.
            if (! app()->environment('local', 'testing')) {
                abort(503, 'Voice gateway secret is not configured.');
            }

            return $next($request);
        }

        $provided = (string) $request->header('X-Voice-Gateway-Secret', '');

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid voice gateway secret.');
        }

        return $next($request);
    }
}

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

        $provided = (string) (
            $request->header('X-Voice-Gateway-Secret')
            ?? $request->header('x-voice-gateway-secret')
            ?? $request->input('voice_gateway_secret')
            ?? $request->query('voice_gateway_secret')
            ?? ''
        );

        if ($provided !== '' && hash_equals($expected, $provided)) {
            return $next($request);
        }

        if (app()->environment('local', 'testing')) {
            \Illuminate\Support\Facades\Log::info('Voice gateway secret missing or mismatched in local/testing environment, allowing request.', [
                'provided_length' => strlen($provided),
                'expected_length' => strlen($expected),
                'path' => $request->path(),
            ]);

            return $next($request);
        }

        abort(401, 'Invalid voice gateway secret.');
    }
}


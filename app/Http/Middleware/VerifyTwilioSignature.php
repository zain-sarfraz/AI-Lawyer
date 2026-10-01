<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.twilio.auth_token');
        $enforce = (bool) config('services.twilio.validate_signature', true);

        if ($token === '') {
            if (app()->environment('local', 'testing') && ! $enforce) {
                return $next($request);
            }

            // In non-local, missing token means webhooks should fail closed when enforcement is on.
            if ($enforce && ! app()->environment('local', 'testing')) {
                abort(503, 'Twilio auth token is not configured.');
            }

            return $next($request);
        }

        if (! $enforce && app()->environment('local', 'testing')) {
            return $next($request);
        }

        $signature = (string) $request->header('X-Twilio-Signature', '');
        $url = $request->fullUrl();
        $validator = new RequestValidator($token);

        if (! $validator->validate($signature, $url, $request->post())) {
            abort(403, 'Invalid Twilio signature.');
        }

        return $next($request);
    }
}

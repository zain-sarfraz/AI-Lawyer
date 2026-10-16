<?php

use App\Http\Middleware\EnsureFirmMember;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\SetFirmContext;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\VerifyTwilioSignature;
use App\Http\Middleware\VerifyVoiceGatewaySecret;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/webhooks')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'firm' => EnsureFirmMember::class,
            'superadmin' => EnsureSuperadmin::class,
            'permission' => EnsurePermission::class,
            'twilio.signature' => VerifyTwilioSignature::class,
            'voice.gateway' => VerifyVoiceGatewaySecret::class,
        ]);

        $middleware->web(append: [SetFirmContext::class]);
        $middleware->api(append: [SetFirmContext::class]);

        $middleware->validateCsrfTokens(except: [
            'api/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

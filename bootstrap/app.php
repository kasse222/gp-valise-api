<?php

use App\Http\Middleware\EnsureKYCValidated;
use App\Http\Middleware\EnsureUserIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ThrottleSensitiveActions;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verified_user'      => EnsureUserIsVerified::class,
            'kyc'                => EnsureKYCValidated::class,
            'force.json'         => ForceJsonResponse::class,
            'throttle.sensitive' => ThrottleSensitiveActions::class,
            'webhook.signature'  => VerifyWebhookSignature::class,
        ]);

        $middleware->appendToGroup('api', ForceJsonResponse::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('horizon') || $request->is('horizon/*') || $request->expectsJson()) {
                return null;
            }

            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        });
    })
    ->create();

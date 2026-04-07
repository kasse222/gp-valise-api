<?php

use App\Http\Middleware\EnsureKYCValidated;
use App\Http\Middleware\EnsureUserIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ThrottleSensitiveActions;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60);
        });
        // Optionnel mais utile : forcer JSON sur toutes les routes API
        $middleware->appendToGroup('api', ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

<?php

use App\Http\Middleware\EnsureKYCValidated;
use App\Http\Middleware\EnsureUserIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ThrottleSensitiveActions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verified_user' => EnsureUserIsVerified::class,
            'kyc'           => EnsureKYCValidated::class,
            'force.json'    => ForceJsonResponse::class,
            'throttle.sensitive' => ThrottleSensitiveActions::class,
        ]);

        // optionnel : forcer JSON sur toutes routes api
        $middleware->appendToGroup('api', \App\Http\Middleware\ForceJsonResponse::class);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

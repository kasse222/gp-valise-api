<?php

namespace App\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class ThrottleSensitiveActions
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $keyPrefix = 'sensitive', int $maxAttempts = 5, int $decayMinutes = 1): Response
    {
        $userKey = $request->user()?->id ? 'u:' . $request->user()->id : 'ip:' . $request->ip();
        $key = $keyPrefix . ':' . $userKey;


        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Trop de tentatives. Réessayez dans ' . $this->limiter->availableIn($key) . ' secondes.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}

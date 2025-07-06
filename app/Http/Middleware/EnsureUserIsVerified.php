<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->verified_user) {
            return response()->json([
                'message' => 'Votre compte n’est pas encore vérifié.',
            ], 403);
        }

        return $next($request);
    }
}

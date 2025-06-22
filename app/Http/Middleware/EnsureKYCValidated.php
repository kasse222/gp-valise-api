<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKYCValidated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->kyc_passed_at) {
            return response()->json([
                'message' => 'Accès refusé – validation KYC requise.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

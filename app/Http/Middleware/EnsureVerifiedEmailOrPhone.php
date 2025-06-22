<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedEmailOrPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->verified_user) {
            return response()->json([
                'message' => 'Veuillez vérifier votre email ou téléphone pour accéder à cette fonctionnalité.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

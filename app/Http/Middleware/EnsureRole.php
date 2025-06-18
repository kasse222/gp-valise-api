<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed ...$roles  // Accepte 1 ou plusieurs rôles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Si l’utilisateur n’est pas connecté ou n’a pas le rôle requis
        if (! $user || ! in_array($user->role->value, $roles)) {
            return response()->json(['message' => 'Accès refusé – rôle requis : ' . implode(', ', $roles)], Response::HTTP_FORBIDDEN);
        }
        //  dans le cas d’un cast mal configuré ou d’un utilisateur corrompu en base
        if (! $user || ! $user->role || ! in_array($user->role->value, $roles)) {
            return response()->json([
                'message' => 'Accès refusé – rôle requis : ' . implode(', ', $roles),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

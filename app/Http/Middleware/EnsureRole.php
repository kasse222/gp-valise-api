<?php

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // 🔐 Vérifie que le rôle est bien un enum attendu
        if (! $user || ! $user->role instanceof UserRoleEnum) {
            report("Utilisateur sans rôle ou rôle invalide : " . optional($user)->id);
            return response()->json([
                'message' => 'Accès refusé – utilisateur non authentifié ou rôle invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        // 🧠 Convertit les noms de rôle en enums, ignore les invalides
        $authorized = collect($roles)
            ->map(fn($role) => UserRoleEnum::tryFrom($role))
            ->filter()
            ->contains($user->role);

        if (! $authorized) {
            report("Tentative d’accès refusée pour l’utilisateur #{$user->id} avec rôle {$user->role->value}");

            return response()->json([
                'message' => 'Accès refusé – rôle requis : ' . implode(', ', $roles),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

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


        if (! $user || ! $user->role instanceof UserRoleEnum) {
            report("Utilisateur sans rôle ou rôle invalide : " . optional($user)->id);
            return response()->json([
                'message' => 'Accès refusé – utilisateur non authentifié ou rôle invalide.',
            ], Response::HTTP_FORBIDDEN);
        }


        $authorized = collect($roles)
            ->map(function ($role) {
                if (is_numeric($role)) {
                    return UserRoleEnum::tryFrom((int) $role);
                }


                $roleConst = strtoupper($role);
                if (defined(UserRoleEnum::class . "::{$roleConst}")) {
                    return constant(UserRoleEnum::class . "::{$roleConst}");
                }

                return null;
            })
            ->filter()
            ->contains($user->role);


        if (! $authorized) {
            report("Accès refusé à l’utilisateur #{$user->id} avec rôle {$user->role->value}");

            return response()->json([
                'message' => 'Accès refusé – rôle requis : ' . implode(', ', $roles),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeatures
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->plan) {
            Log::warning("Utilisateur #{$user->id} n’a pas de plan actif.");
            return response()->json(['message' => 'Aucun plan actif.'], Response::HTTP_FORBIDDEN);
        }

        // ⚠️ Vérification : la colonne 'features' doit être un tableau JSON bien casté
        $features = $user->plan->features;

        if (! is_array($features)) {
            Log::error("Plan #{$user->plan_id} a un champ 'features' mal formaté.");
            return response()->json(['message' => 'Plan invalide ou mal configuré.'], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($feature, $features, true)) {
            Log::info("Accès refusé à la feature '{$feature}' pour l’utilisateur #{$user->id} (plan: {$user->plan_id}).");

            return response()->json([
                'message' => "Fonctionnalité non incluse dans votre plan.",
                'feature' => $feature,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeatures
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $user->plan || ! in_array($feature, $user->plan->features ?? [])) {
            return response()->json(['error' => 'Fonctionnalité inaccessible avec ce plan.'], 403);
        }

        return $next($request);
    }
}

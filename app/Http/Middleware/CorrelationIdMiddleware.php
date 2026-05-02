<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Récupère ou génère un correlation_id
        $correlationId = $request->header('X-Correlation-ID') ?: (string) Str::uuid();

        // 2. Injecte dans la request (accessible partout)
        $request->headers->set('X-Correlation-ID', $correlationId);

        // 3. Ajoute au contexte global des logs
        Log::withContext([
            'correlation_id' => $correlationId,
        ]);

        // 4. Continue la requête
        $response = $next($request);

        // 5. Ajoute dans la réponse HTTP
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}

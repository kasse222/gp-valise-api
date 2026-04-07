<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('payment.webhook.secret');
        $payload = $request->getContent();
        $signatureReceived = $request->header('X-Signature');

        if (! $secret || ! $signatureReceived) {
            abort(403, 'Signature manquante.');
        }

        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($computedSignature, $signatureReceived)) {
            abort(403, 'Signature invalide.');
        }

        return $next($request);
    }
}

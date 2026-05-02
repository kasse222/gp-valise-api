<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        ProcessPaymentWebhook::dispatch(
            payload: $payload,
            correlationId: $request->header('X-Correlation-ID')
        );

        return response()->json([
            'status' => 'accepted',
        ], Response::HTTP_ACCEPTED);
    }
}

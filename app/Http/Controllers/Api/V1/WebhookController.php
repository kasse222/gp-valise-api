<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Payments\WebhookProcessorContract;
use App\Jobs\ProcessPaymentWebhook;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessorContract $processor,
    ) {}

    public function __invoke(Request $request, string $providerKey): Response
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        $event = $this->processor->process($request, $providerKey);

        ProcessPaymentWebhook::dispatch([
            'event_id'                => $event->eventId,
            'event_type'              => $event->eventType,
            'provider'                => $event->provider->value,
            'provider_transaction_id' => $event->providerTransactionId,
            'provider_status'         => $event->providerStatus,
            'amount'                  => $event->amount,
            'currency'                => $event->currency->value,
            'metadata'                => $event->metadata,
            'raw_payload'             => $event->rawPayload,
        ], $correlationId);

        return response()->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}

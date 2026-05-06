<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Contracts\Payments\WebhookProcessorContract;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\PaymentProviderEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class WebhookProcessor implements WebhookProcessorContract
{
    public function __construct(
        private readonly PaymentProviderResolverContract $resolver,
    ) {}

    public function process(Request $request, string $providerKey): PaymentEventData
    {
        $provider = $this->resolver->resolveByKey($providerKey);

        $verification = new WebhookVerificationData(
            provider: PaymentProviderEnum::from($providerKey),
            rawBody: $request->getContent(),
            payload: $request->all(),
            headers: $request->headers->all(),
            signature: $request->header('x-kkiapay-secret')
                ?? $request->header('stripe-signature'),
            correlationId: $request->header('X-Correlation-ID'),
        );

        if (! $provider->verifyWebhook($verification)) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        return $provider->normalizeWebhook($verification);
    }
}

<?php

declare(strict_types=1);

use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->resolver = Mockery::mock(PaymentProviderResolverContract::class);

    $this->provider = Mockery::mock(PaymentProvider::class);

    $this->processor = new WebhookProcessor($this->resolver);
});

it('normalise un webhook kkiapay valide et retourne un PaymentEventData', function (): void {
    $rawBody = json_encode([
        'transactionId' => 'kkp_tx_001',
        'event'         => 'transaction.success',
        'amount'        => 5000,
        'stateData'     => ['booking_id' => 42],
    ]);

    $request = Request::create(
        uri: '/api/v1/webhooks/kkiapay',
        method: 'POST',
        content: $rawBody,
    );
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('x-kkiapay-secret', 'test-secret');

    $this->resolver
        ->shouldReceive('resolveByKey')
        ->once()
        ->with('kkiapay')
        ->andReturn($this->provider);

    $this->provider
        ->shouldReceive('verifyWebhook')
        ->once()
        ->with(Mockery::type(WebhookVerificationData::class))
        ->andReturn(true);

    $this->provider
        ->shouldReceive('normalizeWebhook')
        ->once()
        ->with(Mockery::type(WebhookVerificationData::class))
        ->andReturnUsing(function (WebhookVerificationData $v) {
            return new \App\Data\Payments\PaymentEventData(
                provider: PaymentProviderEnum::KKIAPAY,
                eventId: 'kkp_tx_001',
                eventType: 'transaction.success',
                providerTransactionId: 'kkp_tx_001',
                providerStatus: 'completed',
                amount: 5000,
                currency: \App\Enums\CurrencyEnum::XOF,
            );
        });

    $event = $this->processor->process($request, 'kkiapay');

    expect($event->eventId)->toBe('kkp_tx_001')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->amount)->toBe(5000)
        ->and($event->provider)->toBe(PaymentProviderEnum::KKIAPAY);
});

it('lève AccessDeniedHttpException si la signature est invalide', function (): void {
    $rawBody = json_encode(['transactionId' => 'kkp_tx_002', 'event' => 'transaction.success']);

    $request = Request::create(
        uri: '/api/v1/webhooks/kkiapay',
        method: 'POST',
        content: $rawBody,
    );
    $request->headers->set('x-kkiapay-secret', 'wrong-secret');

    $this->resolver
        ->shouldReceive('resolveByKey')
        ->once()
        ->with('kkiapay')
        ->andReturn($this->provider);

    $this->provider
        ->shouldReceive('verifyWebhook')
        ->once()
        ->andReturn(false);

    expect(fn() => $this->processor->process($request, 'kkiapay'))
        ->toThrow(AccessDeniedHttpException::class);
});

it('passe rawBody exact au WebhookVerificationData', function (): void {
    $rawBody = '{"transactionId":"kkp_tx_003","event":"transaction.failed","amount":1000}';

    $request = Request::create(
        uri: '/api/v1/webhooks/kkiapay',
        method: 'POST',
        content: $rawBody,
    );
    $request->headers->set('x-kkiapay-secret', 'test-secret');

    $this->resolver
        ->shouldReceive('resolveByKey')
        ->with('kkiapay')
        ->andReturn($this->provider);

    $this->provider
        ->shouldReceive('verifyWebhook')
        ->once()
        ->with(Mockery::on(function (WebhookVerificationData $v) use ($rawBody): bool {
            return $v->rawBody === $rawBody
                && $v->provider === PaymentProviderEnum::KKIAPAY;
        }))
        ->andReturn(true);

    $this->provider
        ->shouldReceive('normalizeWebhook')
        ->once()
        ->andReturnUsing(fn() => new \App\Data\Payments\PaymentEventData(
            provider: PaymentProviderEnum::KKIAPAY,
            eventId: 'kkp_tx_003',
            eventType: 'transaction.failed',
            providerTransactionId: 'kkp_tx_003',
            providerStatus: 'failed',
            amount: 1000,
            currency: \App\Enums\CurrencyEnum::XOF,
        ));

    $event = $this->processor->process($request, 'kkiapay');

    expect($event->eventType)->toBe('transaction.failed');
});

it('propage le correlationId depuis le header vers WebhookVerificationData', function (): void {
    $rawBody = json_encode(['transactionId' => 'kkp_tx_004', 'event' => 'transaction.success']);

    $request = Request::create(
        uri: '/api/v1/webhooks/kkiapay',
        method: 'POST',
        content: $rawBody,
    );
    $request->headers->set('x-kkiapay-secret', 'test-secret');
    $request->headers->set('X-Correlation-ID', 'corr-abc-123');

    $this->resolver
        ->shouldReceive('resolveByKey')
        ->andReturn($this->provider);

    $this->provider
        ->shouldReceive('verifyWebhook')
        ->with(Mockery::on(function (WebhookVerificationData $v): bool {
            return $v->correlationId === 'corr-abc-123';
        }))
        ->andReturn(true);

    $this->provider
        ->shouldReceive('normalizeWebhook')
        ->andReturnUsing(fn() => new \App\Data\Payments\PaymentEventData(
            provider: PaymentProviderEnum::KKIAPAY,
            eventId: 'kkp_tx_004',
            eventType: 'transaction.success',
            providerTransactionId: 'kkp_tx_004',
            providerStatus: 'completed',
            amount: 2000,
            currency: \App\Enums\CurrencyEnum::XOF,
        ));

    $event = $this->processor->process($request, 'kkiapay');

    expect($event->eventId)->toBe('kkp_tx_004');
});

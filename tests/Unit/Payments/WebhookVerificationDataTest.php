<?php

declare(strict_types=1);

use App\Data\Payments\WebhookVerificationData;
use App\Enums\PaymentProviderEnum;

it('instancie correctement WebhookVerificationData', function () {
    $data = new WebhookVerificationData(
        provider: PaymentProviderEnum::FAKE,
        rawBody: '{"event":"test"}',
        payload: ['event' => 'test'],
        headers: ['x-signature' => 'abc123'],
        signature: 'abc123',
        eventId: 'evt_123',
        correlationId: 'corr_123',
    );

    expect($data->provider)->toBe(PaymentProviderEnum::FAKE);
    expect($data->rawBody)->toBe('{"event":"test"}');
    expect($data->payload)->toBe(['event' => 'test']);
    expect($data->headers)->toHaveKey('x-signature');
    expect($data->signature)->toBe('abc123');
    expect($data->eventId)->toBe('evt_123');
    expect($data->correlationId)->toBe('corr_123');
});

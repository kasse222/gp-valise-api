<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentRequestData;

interface PaymentProviderResolverContract
{
    public function resolve(PaymentRequestData $request): PaymentProvider;
    public function resolveByKey(string $providerKey): PaymentProvider;
}

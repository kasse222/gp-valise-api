<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\KkiapayAdminClientContract;
use Kkiapay\Kkiapay;
use RuntimeException;

final class KkiapayAdminClient implements KkiapayAdminClientContract
{
    private ?Kkiapay $sdk = null;

    private function sdk(): Kkiapay
    {
        if ($this->sdk !== null) {
            return $this->sdk;
        }

        $publicKey  = config('payment_providers.kkiapay.public_key');
        $privateKey = config('payment_providers.kkiapay.private_key');
        $secret     = config('payment_providers.kkiapay.secret');
        $sandbox    = (bool) config('payment_providers.kkiapay.sandbox', true);

        if (! is_string($publicKey) || $publicKey === '') {
            throw new RuntimeException('Kkiapay public key is not configured.');
        }
        if (! is_string($privateKey) || $privateKey === '') {
            throw new RuntimeException('Kkiapay private key is not configured.');
        }
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Kkiapay secret is not configured.');
        }

        return $this->sdk = new Kkiapay($publicKey, $privateKey, $secret, $sandbox);
    }

    public function verify(string $transactionId): array
    {
        $result = $this->sdk()->verifyTransaction($transactionId);
        return (array) $result;
    }

    public function refund(string $transactionId): array|bool
    {
        $result = $this->sdk()->refundTransaction($transactionId);

        if ($result === false || $result === null) {
            return false;
        }

        return (array) $result;
    }
}

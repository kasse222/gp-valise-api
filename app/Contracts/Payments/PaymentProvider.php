<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\WebhookVerificationData;

interface PaymentProvider
{
    /**
     * Initie un paiement (charge)
     */
    public function charge(PaymentRequestData $request): PaymentResponseData;

    /**
     * Effectue un remboursement
     */
    public function refund(RefundRequestData $request): PaymentResponseData;

    /**
     * Vérifie la validité d’un webhook (signature, etc.)
     */
    public function verifyWebhook(WebhookVerificationData $webhook): bool;

    /**
     * Transforme un webhook brut en événement interne normalisé
     */
    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData;

    /**
     * Nom du provider (kkiapay, stripe, etc.)
     */
    public function name(): string;
}

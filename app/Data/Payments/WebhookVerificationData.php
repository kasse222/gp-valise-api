<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\PaymentProviderEnum;

final class WebhookVerificationData
{
    public function __construct(
        /**
         * Provider ciblé (route → provider)
         */
        public readonly PaymentProviderEnum $provider,

        /**
         * Corps brut du webhook (string EXACT reçu)
         * → utilisé pour la vérification de signature
         */
        public readonly string $rawBody,

        /**
         * Payload décodé (JSON → array)
         * → utilisé après vérification
         */
        public readonly array $payload,

        /**
         * Tous les headers HTTP (normalisés en lower-case si possible)
         */
        public readonly array $headers,

        /**
         * Signature extraite des headers (si applicable)
         */
        public readonly ?string $signature = null,

        /**
         * ID d’événement (si dispo sans parser profond)
         * → fallback : sera extrait dans normalizeWebhook()
         */
        public readonly ?string $eventId = null,

        /**
         * Correlation ID propagé (ou généré côté controller)
         */
        public readonly ?string $correlationId = null,
    ) {}
}

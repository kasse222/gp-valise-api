<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;

interface AggregatorDriver
{
    /**
     * Charge via le provider primaire — bascule sur le fallback si indisponible.
     */
    public function charge(PaymentRequestData $request): PaymentResponseData;

    /**
     * Refund via le provider qui a effectué la charge originale.
     */
    public function refund(RefundRequestData $request): PaymentResponseData;

    /**
     * Vérifie si au moins un provider de l'agrégateur est disponible.
     */
    public function isAvailable(): bool;

    /**
     * Retourne la liste des providers sous-jacents.
     *
     * @return string[]
     */
    public function getProviders(): array;

    /**
     * Retourne la clé du provider actuellement actif (primaire ou fallback).
     */
    public function getActiveProvider(): string;
}

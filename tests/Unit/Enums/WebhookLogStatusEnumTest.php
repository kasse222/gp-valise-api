<?php

use App\Enums\WebhookLogStatusEnum;

it('a les bonnes valeurs de statut', function () {
    expect(WebhookLogStatusEnum::RECEIVED->value)->toBe('received')
        ->and(WebhookLogStatusEnum::PROCESSED->value)->toBe('processed')
        ->and(WebhookLogStatusEnum::IGNORED->value)->toBe('ignored')
        ->and(WebhookLogStatusEnum::FAILED->value)->toBe('failed');
});

it('retourne les bons labels', function () {
    expect(WebhookLogStatusEnum::RECEIVED->label())->toBe('Reçu')
        ->and(WebhookLogStatusEnum::PROCESSED->label())->toBe('Traité')
        ->and(WebhookLogStatusEnum::IGNORED->label())->toBe('Ignoré')
        ->and(WebhookLogStatusEnum::FAILED->label())->toBe('Échoué');
});

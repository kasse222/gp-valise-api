<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookLogStatusEnum: string
{
    case RECEIVED  = 'received';
    case PROCESSED = 'processed';
    case IGNORED   = 'ignored';
    case FAILED    = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED  => 'Reçu',
            self::PROCESSED => 'Traité',
            self::IGNORED   => 'Ignoré',
            self::FAILED    => 'Échoué',
        };
    }
}

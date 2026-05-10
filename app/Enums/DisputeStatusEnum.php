<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeStatusEnum: string
{
    case OPEN             = 'open';
    case UNDER_REVIEW     = 'under_review';
    case WAITING_CUSTOMER = 'waiting_customer';
    case WAITING_TRAVELER = 'waiting_traveler';
    case ESCALATED        = 'escalated';
    case RESOLVED         = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::OPEN             => 'Ouvert',
            self::UNDER_REVIEW     => 'En cours d\'analyse',
            self::WAITING_CUSTOMER => 'En attente expéditeur',
            self::WAITING_TRAVELER => 'En attente voyageur',
            self::ESCALATED        => 'Escaladé',
            self::RESOLVED         => 'Résolu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN             => 'warning',
            self::UNDER_REVIEW     => 'info',
            self::WAITING_CUSTOMER => 'warning',
            self::WAITING_TRAVELER => 'warning',
            self::ESCALATED        => 'danger',
            self::RESOLVED         => 'success',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::OPEN             => [self::UNDER_REVIEW, self::ESCALATED, self::RESOLVED],
            self::UNDER_REVIEW     => [self::WAITING_CUSTOMER, self::WAITING_TRAVELER, self::ESCALATED, self::RESOLVED],
            self::WAITING_CUSTOMER => [self::UNDER_REVIEW, self::ESCALATED, self::RESOLVED],
            self::WAITING_TRAVELER => [self::UNDER_REVIEW, self::ESCALATED, self::RESOLVED],
            self::ESCALATED        => [self::UNDER_REVIEW, self::RESOLVED],
            self::RESOLVED         => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::RESOLVED;
    }
}

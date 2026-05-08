<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\TripStatusEnum;
use App\Models\Trip;

class CanBeReserved
{
    public static function handle(Trip $trip): bool
    {
        if ($trip->gramsDisponible() <= 0) {
            return false;
        }

        if ($trip->date === null || $trip->date->isPast()) {
            return false;
        }

        if (! $trip->status instanceof TripStatusEnum) {
            return false;
        }

        return $trip->status->isReservable();
    }
}

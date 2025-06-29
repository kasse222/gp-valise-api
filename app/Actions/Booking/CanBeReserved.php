<?php

namespace App\Actions\Booking;

use App\Models\Trip;
use App\Enums\TripStatusEnum;

class CanBeReserved
{
    public static function handle(Trip $trip): bool
    {
        if ($trip->kgDisponible() <= 0) {
            return false;
        }

        if (!$trip->date || $trip->date->isPast()) {
            return false;
        }

        if (!$trip->status instanceof TripStatusEnum || !$trip->status->isReservable()) {
            return false;
        }

        return true;
    }
}

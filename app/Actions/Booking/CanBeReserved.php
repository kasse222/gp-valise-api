<?php

namespace App\Actions\Booking;

use App\Models\Trip;

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

        if (method_exists($trip, 'status') && !$trip->status->isReservable()) {
            return false;
        }

        return true;
    }
}

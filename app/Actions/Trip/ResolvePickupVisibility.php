<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;

class ResolvePickupVisibility
{
    /**
     * Détermine si l'utilisateur peut voir les adresses exactes de pickup/delivery.
     *
     * @return array{isOwner: bool, revealed: bool}
     */
    public static function handle(Trip $trip, ?User $user): array
    {
        if (!$user) {
            return ['isOwner' => false, 'revealed' => false];
        }

        $isOwner = (int) $user->id === (int) $trip->user_id;

        if ($isOwner) {
            return ['isOwner' => true, 'revealed' => false];
        }

        // Sender → révéler si booking confirmé/livré/terminé sur ce trajet
        $revealed = Booking::query()
            ->where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmee', 'livree', 'termine'])
            ->exists();

        return ['isOwner' => false, 'revealed' => $revealed];
    }
}

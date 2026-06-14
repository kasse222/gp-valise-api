<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetUserBookings
{
    public function execute(User $user): Collection
    {
        $relations = [
            'bookingItems.luggage',
            'trip.user',
            'statusHistories',
        ];

        $query = Booking::query()
            ->with($relations)
            ->latest();

        if ($user->isVoyageur()) {
            // Instant Booking : pas de filtre PENDING_APPROVAL
            // On exclut simplement les EN_PAIEMENT expirés pour ne pas polluer la liste traveler
            return $query
                ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
                ->where(function ($q) {
                    $q->where('status', '!=', \App\Enums\BookingStatusEnum::EN_PAIEMENT)
                        ->orWhereNull('payment_expires_at')
                        ->orWhere('payment_expires_at', '>', now());
                })
                ->get();
        }

        return $query
            ->where('user_id', $user->id)
            ->get();
    }
}

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
            return $query
                ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
                ->get();
        }

        return $query
            ->where('user_id', $user->id)
            ->get();
    }
}

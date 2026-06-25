<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil public d'un voyageur (GP).
 * On expose uniquement les trajets RÉSERVABLES (scopeReservable) :
 * statut ACTIVE/PENDING + date future + capacité non saturée.
 */
class TravelerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $this */
        $reservableTrips = $this->trips()
            ->reservable()
            ->with(['locations'])
            ->latest()
            ->get();

        return [
            'id'           => $this->id,
            'first_name'   => $this->first_name,
            'country'      => $this->country,
            'member_since' => $this->created_at->format('M Y'),
            'kyc_verified' => $this->hasKyc(),

            'active_trips_count' => $reservableTrips->count(),
            'total_trips_count'  => $this->trips()->count(),

            'active_trips' => TripResource::collection($reservableTrips),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\TripStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeTrips = $this->trips()
            ->where('status', TripStatusEnum::ACTIVE)
            ->with(['locations'])
            ->latest()
            ->get();

        return [
            'id'           => $this->id,
            'first_name'   => $this->first_name,
            'country'      => $this->country,
            'member_since' => $this->created_at->format('M Y'),
            'kyc_verified' => $this->hasKyc(),

            'active_trips_count' => $activeTrips->count(),
            'total_trips_count'  => $this->trips()->count(),

            'active_trips' => TripResource::collection($activeTrips),
        ];
    }
}

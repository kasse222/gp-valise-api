<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTrips
{
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Trip::query()
            ->with(['user', 'locations', 'categoryFees'])
            ->reservable()
            ->when(
                ! empty($filters['departure']),
                fn($q) => $q->whereRaw('LOWER(departure) LIKE ?', ['%' . strtolower($filters['departure']) . '%'])
            )
            ->when(
                ! empty($filters['destination']),
                fn($q) => $q->whereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($filters['destination']) . '%'])
            )
            ->when(
                ! empty($filters['date']),
                fn($q) => $q->whereDate('date', $filters['date'])
            )
            ->when(
                ! empty($filters['price_max']),
                fn($q) => $q->where('price_per_kg', '<=', (int) $filters['price_max'])
            )
            ->when(
                ! empty($filters['capacity_min']),
                fn($q) => $q->whereRaw(
                    'capacity - COALESCE((SELECT SUM(kg_reserved) FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id WHERE b.trip_id = trips.id AND b.status NOT IN (\'annule\', \'expiree\', \'declined_by_traveler\')), 0) >= ?',
                    [(int) $filters['capacity_min']]
                )
            )
            ->latest()
            ->paginate($perPage);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Trip;

use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTrips
{
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return Trip::query()
            ->with(['user', 'locations'])
            ->reservable()
            ->latest()
            ->paginate($perPage);
    }
}

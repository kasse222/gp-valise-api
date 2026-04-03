<?php

namespace App\Actions\Trip;

use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTrips
{
    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        return Trip::query()
            ->with(['user'])
            ->latest()
            ->paginate($perPage);
    }
}

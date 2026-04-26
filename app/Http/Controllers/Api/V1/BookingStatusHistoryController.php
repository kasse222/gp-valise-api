<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BookingStatusHistoryResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

class BookingStatusHistoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Booking $booking)
    {
        $this->authorize('view', $booking);

        $histories = $booking
            ->statusHistories()
            ->latest()
            ->get();

        return BookingStatusHistoryResource::collection($histories);
    }
}

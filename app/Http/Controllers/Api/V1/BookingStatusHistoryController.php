<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\StatusHistory\StoreBookingStatusHistoryRequest;
use App\Http\Resources\BookingStatusHistoryResource;
use App\Models\Booking;
use App\Models\BookingStatusHistory;

class BookingStatusHistoryController extends Controller
{
    public function index(Booking $booking)
    {
        $this->authorize('view', $booking);
        return BookingStatusHistoryResource::collection($booking->statusHistories()->latest()->get());
    }

    public function store(StoreBookingStatusHistoryRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);
        $history = $booking->statusHistories()->create($request->validated());
        return new BookingStatusHistoryResource($history);
    }
}

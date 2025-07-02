<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\CreateBookingStatusHistory;
use Illuminate\Routing\Controller;
use App\Http\Requests\BookingStatusHistory\StoreBookingStatusHistoryRequest;
use App\Http\Resources\BookingStatusHistoryResource;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BookingStatusHistoryController extends Controller
{
    use AuthorizesRequests;
    public function index(Booking $booking)
    {
        $this->authorize('view', $booking);
        return BookingStatusHistoryResource::collection($booking->statusHistories()->latest()->get());
    }

    public function store(StoreBookingStatusHistoryRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $history = CreateBookingStatusHistory::execute($booking, $request->validated());

        return new BookingStatusHistoryResource($history);
    }
}

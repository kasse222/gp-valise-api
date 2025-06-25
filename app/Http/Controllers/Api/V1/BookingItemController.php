<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\BookingItem\StoreBookingItemRequest;
use App\Http\Requests\BookingItem\UpdateBookingItemRequest;
use App\Http\Resources\BookingItemResource;
use App\Models\Booking;
use App\Models\BookingItem;

class BookingItemController extends Controller
{
    public function index(Booking $booking)
    {
        $this->authorize('view', $booking);
        return BookingItemResource::collection($booking->bookingItems()->get());
    }

    public function store(StoreBookingItemRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);
        $item = $booking->bookingItems()->create($request->validated());
        return new BookingItemResource($item);
    }

    public function update(UpdateBookingItemRequest $request, BookingItem $item)
    {
        $this->authorize('update', $item);
        $item->update($request->validated());
        return new BookingItemResource($item);
    }

    public function destroy(BookingItem $item)
    {
        $this->authorize('delete', $item);
        $item->delete();
        return response()->json(['message' => 'Élément supprimé.']);
    }
}

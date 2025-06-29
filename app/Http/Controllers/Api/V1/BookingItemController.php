<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BookingItem\CreateBookingItem;
use App\Actions\BookingItem\UpdateBookingItem;
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
        // 🧠 On trie pour UX / prédictibilité
        return BookingItemResource::collection(
            $booking->bookingItems()->orderBy('created_at')->get()
        );
    }

    public function store(StoreBookingItemRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $item = CreateBookingItem::execute($booking, $request->validated());

        return new BookingItemResource($item);
    }

    public function update(UpdateBookingItemRequest $request, BookingItem $item)
    {
        $this->authorize('update', $item);

        $item = UpdateBookingItem::execute($item, $request->validated());

        return new BookingItemResource($item);
    }

    public function destroy(BookingItem $item)
    {
        $this->authorize('delete', $item);
        $item->delete();
        return response()->json(['message' => 'Élément supprimé.']);
    }
}

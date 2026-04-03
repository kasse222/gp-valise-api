<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\DeleteBooking;
use App\Actions\Booking\GetBookingDetails;
use App\Actions\Booking\GetUserBookings;
use App\Actions\Booking\ReserveBooking;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
    use AuthorizesRequests;

    /**
     * 📦 Lister les réservations de l'utilisateur
     */
    public function index(Request $request, GetUserBookings $action)
    {
        $bookings = $action->execute($request->user());

        return BookingResource::collection($bookings);
    }

    /**
     * 🛒 Réserver une valise pour un trajet
     */
    public function store(StoreBookingRequest $request, ReserveBooking $action)
    {
        $booking = $action->execute($request->user(), $request->validated());

        return (new BookingResource($booking->load('bookingItems.luggage')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 🔍 Voir une réservation précise
     */
    public function show(Booking $booking, GetBookingDetails $action)
    {
        $this->authorize('view', $booking);

        $booking = $action->execute($booking->id);

        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }

    /**
     * ❌ Supprimer une réservation
     */
    public function destroy(Booking $booking, DeleteBooking $action)
    {
        $this->authorize('delete', $booking);

        $action->execute($booking);

        return response()->json([
            'message' => 'Réservation supprimée.',
        ]);
    }

    /**
     * ✅ Confirmer une réservation
     */
    public function confirm(Request $request, Booking $booking, ConfirmBooking $action)
    {
        $this->authorize('confirm', $booking);

        $booking = $action->execute($booking, $request->user());

        return new BookingResource($booking->load('bookingItems.luggage'));
    }

    /**
     * ❌ Annuler une réservation
     */
    public function cancel(Request $request, Booking $booking, CancelBooking $action)
    {
        $this->authorize('cancel', $booking);

        $booking = $action->execute($booking, $request->user());

        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }

    /**
     * 📦 Marquer comme livrée
     */
    public function complete(Request $request, Booking $booking, CompleteBooking $action)
    {
        $this->authorize('complete', $booking);

        $booking = $action->execute($booking, $request->user());

        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => new BookingResource($booking->load('bookingItems.luggage')),
        ]);
    }
}

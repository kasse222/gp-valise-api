<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\DeleteBooking;
use App\Actions\Booking\GetBookingDetails;
use App\Actions\Booking\GetUserBookings;
use App\Actions\Booking\ReserveBooking;
use App\Actions\Booking\UpdateBookingStatus;
use App\Enums\BookingStatusEnum;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use AuthorizesRequests;
    /**
     * 📦 Lister les réservations de l'utilisateur (voyageur)
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
        $booking = $action->execute($request->validated());

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
     * 🔁 Modifier le statut d'une réservation
     */
    public function update(
        UpdateBookingRequest $request,
        Booking $booking,
        UpdateBookingStatus $action
    ) {
        $this->authorize('update', $booking);

        $newStatus = BookingStatusEnum::from($request->validated('status'));

        $booking = $action->execute($booking, $newStatus, $request->user());

        return new BookingResource($booking->load('bookingItems.luggage'));
    }


    /**
     * ❌ Supprimer une réservation
     */
    public function destroy(Booking $booking, DeleteBooking $action)
    {
        $this->authorize('delete', $booking);

        $action->execute($booking);

        return response()->json(['message' => 'Réservation supprimée.']);
    }

    /**
     * ✅ Confirmer une réservation
     */
    public function confirm(Booking $booking, ConfirmBooking $action)
    {
        $this->authorize('confirm', $booking);

        $booking = $action->execute($booking->id);

        return new BookingResource($booking->load('bookingItems.luggage'));
    }


    /**
     * ❌ Annuler une réservation
     */
    public function cancel(Booking $booking, CancelBooking $action)
    {
        $this->authorize('cancel', $booking);

        $booking = $action->execute($booking->id);

        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }

    /**
     * 📦 Marquer comme livrée
     */
    public function complete(Booking $booking, CompleteBooking $action)
    {
        $this->authorize('complete', $booking);

        $booking = $action->execute($booking);

        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => new BookingResource($booking->load('bookingItems.luggage')),
        ]);
    }
}

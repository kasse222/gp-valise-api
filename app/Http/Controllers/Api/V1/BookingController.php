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
    public function index(Request $request)
    {

        $bookings = GetUserBookings::execute($request->user());

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
    public function show(string $id)
    {
        $booking = GetBookingDetails::execute($id);

        // Optionnel : $this->authorize('view', $booking);
        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }

    /**
     * 🔁 Modifier le statut d'une réservation
     */
    public function update(UpdateBookingRequest $request,  Booking $booking)
    {
        $this->authorize('update', $booking);

        $newStatus = BookingStatusEnum::from($request->validated('status'));

        $booking = UpdateBookingStatus::execute($booking, $newStatus, $request->user());

        return new BookingResource($booking->load('bookingItems.luggage'));
    }


    /**
     * ❌ Supprimer une réservation
     */
    public function destroy(string $id)
    {
        $booking = Booking::with('bookingItems.luggage')->findOrFail($id);

        $this->authorize('delete', $booking);

        DeleteBooking::execute($booking);

        return response()->json(['message' => 'Réservation supprimée.']);
    }

    /**
     * ✅ Confirmer une réservation
     */
    public function confirm(string $id, ConfirmBooking $action)
    {
        $booking = Booking::with('trip')->findOrFail($id);

        $this->authorize('confirm', $booking);

        $booking = $action->execute((int) $id);

        return new BookingResource($booking->load('bookingItems.luggage'));
    }

    /**
     * ❌ Annuler une réservation
     */
    public function cancel(string $id, CancelBooking $action)
    {
        $booking = Booking::with(['trip', 'bookingItems.luggage'])->findOrFail($id);

        $this->authorize('cancel', $booking);

        $booking = $action->execute((int) $id);

        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }

    /**
     * 📦 Marquer comme livrée
     */
    public function complete(string $id, CompleteBooking $action)
    {
        // 1. Récupération de la réservation
        $booking = Booking::findOrFail($id);

        // 2. Vérification d'autorisation via Policy
        $this->authorize('complete', $booking);

        // 3. Exécution de la logique métier dans l'action
        $booking = $action->execute($booking);

        // 4. Réponse avec Resource + message clair
        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => new BookingResource($booking->load('bookingItems.luggage')),
        ]);
    }
}

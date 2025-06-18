<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\ReserveBooking;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Trip;
use App\Status\BookingStatus;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * 📦 Lister les réservations de l'utilisateur (propriétaire du trajet)
     */
    public function index()
    {
        $user = Auth::user();

        $bookings = Booking::with(['bookingItems.luggage', 'trip'])
            ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
            ->get();

        return response()->json($bookings);
    }

    /**
     * 🛒 Réserver une valise pour un trajet
     */
    public function store(StoreBookingRequest $request, ReserveBooking $action)
    {
        // Optionnel : autorisation explicite si besoin de logique complexe
        // $this->authorize('create', Booking::class);

        $booking = $action->execute($request->validated());

        return response()->json([
            'message' => 'Réservation créée.',
            'booking' => $booking->load('bookingItems.luggage'),
        ], 201);
    }

    /**
     * 🔍 Voir une réservation précise
     */
    public function show(string $id)
    {
        $booking = Booking::with(['bookingItems.luggage', 'trip'])->findOrFail($id);

        // Optionnel : vérifier accès via policy
        // $this->authorize('view', $booking);

        return response()->json($booking);
    }

    /**
     * 🔁 Modifier le statut d'une réservation
     */
    public function update(UpdateBookingRequest $request, string $id)
    {
        $booking = Booking::with('trip')->findOrFail($id);

        $this->authorize('update', $booking);

        $newStatus = BookingStatus::from($request->validated('status'));

        if (! $booking->canBeUpdatedTo($newStatus, auth()->user())) {
            abort(403, 'Transition de statut non autorisée.');
        }

        $booking->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'booking' => $booking,
        ]);
    }

    /**
     * ❌ Supprimer une réservation
     */
    public function destroy(string $id)
    {
        $booking = Booking::with('bookingItems')->findOrFail($id);

        $this->authorize('delete', $booking);

        foreach ($booking->bookingItems as $item) {
            $item->luggage->update(['status' => 'en_attente']);
            $item->delete();
        }

        $booking->delete();

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

        return response()->json([
            'message' => 'Réservation confirmée.',
            'booking' => $booking,
        ]);
    }

    /**
     * ❌ Annuler une réservation
     */
    public function cancel(string $id, CancelBooking $action)
    {
        $booking = Booking::with(['trip', 'bookingItems.luggage'])->findOrFail($id);

        $this->authorize('cancel', $booking);

        $booking = $action->execute((int) $id);

        return response()->json([
            'message' => 'Réservation annulée.',
            'booking' => $booking,
        ]);
    }

    /**
     * 📦 Marquer comme livrée
     */
    public function complete(string $id, CompleteBooking $action)
    {
        $booking = Booking::with('trip')->findOrFail($id);

        $this->authorize('complete', $booking);

        $booking = $action->execute((int) $id);

        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => $booking,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\ReserveBooking;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * 📦 Lister les réservations de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        // Récupère les bookings avec les valises réservées et le trajet lié
        $bookings = Booking::with(['bookingItems.luggage', 'trip'])
            ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
            ->get();

        return response()->json($bookings);
    }

    /**
     * 🎯 Créer une nouvelle réservation — avec logique métier (à implémenter)
     */
    public function store(StoreBookingRequest $request, ReserveBooking $action)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $trip = Trip::findOrFail($validated['trip_id']);
        $booking = Booking::create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'status'  => 'en_attente',
        ]);

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

        return response()->json($booking);
    }

    /**
     * ✏️ Mettre à jour une réservation (statut par exemple)
     */
    public function update(UpdateBookingRequest $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $booking->update([
            'status' => $request->validated('status'),
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'booking' => $booking
        ]);
    }

    /**
     * ❌ Supprimer une réservation (et ses booking_items associés)
     */
    public function destroy(string $id)
    {
        $booking = Booking::with('bookingItems')->findOrFail($id);

        // ⚠️ On libère les valises associées
        foreach ($booking->bookingItems as $item) {
            $item->luggage->update(['status' => 'en_attente']);
            $item->delete();
        }

        $booking->delete();

        return response()->json(['message' => 'Réservation supprimée.']);
    }

    /**
     * ✅ Confirmer une réservation (par le voyageur propriétaire du trip)
     */
    public function confirm(string $id, ConfirmBooking $action)
    {
        $booking = $action->execute((int) $id);

        return response()->json([
            'message' => 'Réservation confirmée.',
            'booking' => $booking
        ]);
    }
}

<?php

namespace App\Actions\Booking;

use App\Actions\BookingItem\CreateBookingItem;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBooking
{
    /**
     * Réserve un trajet avec des valises données.
     */
    public function execute(array $validated): Booking
    {
        return DB::transaction(function () use ($validated) {
            $user = Auth::user();
            $trip = Trip::findOrFail($validated['trip_id']);

            // 🧮 Validation de la capacité totale
            $totalKg = array_sum(array_column($validated['items'], 'kg_reserved'));
            if (! $trip->canAcceptKg($totalKg)) {
                throw ValidationException::withMessages([
                    'items' => ["La capacité restante du trajet est insuffisante."]
                ]);
            }

            // ✅ Création de la réservation (plus de luggage_id ici)
            $booking = Booking::create([
                'user_id'            => $user->id,
                'trip_id'            => $trip->id,
                'status'             => BookingStatusEnum::EN_PAIEMENT,
                'payment_expires_at' => now()->addMinutes(15),
            ]);

            // 🔁 Création des booking items
            foreach ($validated['items'] as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);
                $this->assertLuggageDisponible($luggage);

                CreateBookingItem::execute($booking, [
                    ...$item,
                    'trip_id' => $trip->id,
                ]);

                // 🟡 Mise à jour du statut de la valise
                $luggage->update(['status' => LuggageStatusEnum::RESERVEE]);
            }

            return $booking->load('bookingItems.luggage');
        });
    }

    protected function assertLuggageDisponible(Luggage $luggage): void
    {
        if ($luggage->status !== LuggageStatusEnum::EN_ATTENTE) {
            throw ValidationException::withMessages([
                'items' => ["La valise #{$luggage->id} n'est pas disponible."]
            ]);
        }
    }
}

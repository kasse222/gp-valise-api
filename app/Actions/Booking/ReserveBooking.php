<?php

namespace App\Actions\Booking;

use App\Actions\BookingItem\CreateBookingItem;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBooking
{
    /**
     * Réserve un trajet avec des valises données.
     */
    public function execute(User $user, array $validated): Booking
    {
        return DB::transaction(function () use ($user, $validated) {
            $now = now();

            $trip = Trip::query()
                ->whereKey($validated['trip_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $totalKg = array_sum(array_column($validated['items'], 'kg_reserved'));

            if (! $trip->canAcceptKg($totalKg)) {
                throw ValidationException::withMessages([
                    'items' => ['La capacité restante du trajet est insuffisante.'],
                ]);
            }

            $luggageIds = collect($validated['items'])
                ->pluck('luggage_id')
                ->unique();

            if ($luggageIds->count() !== count($validated['items'])) {
                throw ValidationException::withMessages([
                    'items' => ['Une valise ne peut pas apparaître plusieurs fois dans la même réservation.'],
                ]);
            }

            $luggages = Luggage::query()
                ->whereIn('id', $luggageIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($luggages->count() !== $luggageIds->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Une ou plusieurs valises sont introuvables.'],
                ]);
            }

            foreach ($luggages as $luggage) {
                if ($luggage->user_id !== $user->id) {
                    throw ValidationException::withMessages([
                        'items' => ['Une ou plusieurs valises ne vous appartiennent pas.'],
                    ]);
                }

                $this->assertLuggageDisponible($luggage);
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'status' => BookingStatusEnum::EN_PAIEMENT,
                'payment_expires_at' => $now->copy()->addMinutes(15),
            ]);

            foreach ($validated['items'] as $item) {
                $luggage = $luggages->get($item['luggage_id']);

                CreateBookingItem::execute($booking, [
                    ...$item,
                    'trip_id' => $trip->id,
                ]);

                $luggage->update([
                    'status' => LuggageStatusEnum::RESERVEE,
                ]);
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

<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Validators\BookingValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBooking
{
    public function __construct(
        protected BookingValidator $validator
    ) {}

    public function execute(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            /** @var Trip $trip */
            $trip = Trip::query()
                ->lockForUpdate()
                ->findOrFail($data['trip_id']);

            $this->validator->validateReservation($trip, $data);

            $luggageIds = collect($data['items'] ?? [])
                ->pluck('luggage_id')
                ->filter()
                ->unique()
                ->values();

            $luggages = Luggage::query()
                ->whereIn('id', $luggageIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($luggages->count() !== $luggageIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Une ou plusieurs valises sont introuvables.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $data['user_id'],
                'trip_id' => $trip->id,
                'status' => BookingStatusEnum::EN_PAIEMENT,
                'payment_expires_at' => now()->addMinutes(15),
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $luggage = $luggages->get($itemData['luggage_id']);

                if (! $luggage) {
                    throw ValidationException::withMessages([
                        'items' => 'Valise introuvable pendant la réservation.',
                    ]);
                }

                if ($luggage->status !== LuggageStatusEnum::EN_ATTENTE) {
                    throw ValidationException::withMessages([
                        'items' => "La valise {$luggage->id} n’est plus disponible.",
                    ]);
                }

                $booking->bookingItems()->create([
                    'trip_id' => $trip->id,
                    'luggage_id' => $luggage->id,
                    'kg_reserved' => $itemData['kg_reserved'],
                    'price' => $itemData['price'],
                ]);

                $luggage->update([
                    'status' => LuggageStatusEnum::RESERVEE,
                ]);
            }

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });
    }
}

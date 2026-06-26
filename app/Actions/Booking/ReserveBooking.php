<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use App\Validators\BookingValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBooking
{
    public function __construct(
        protected BookingValidator $validator
    ) {}

    public function execute(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            $trip = Trip::query()
                ->lockForUpdate()
                ->with('categoryFees')
                ->findOrFail($data['trip_id']);

            $this->validator->validateReservation($user, $trip, $data);

            $this->validateRecipient($data);

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

            // Instant Booking : statut initial EN_PAIEMENT (pas PENDING_APPROVAL)
            $booking = Booking::query()->create([
                'user_id'          => $user->id,
                'trip_id'          => $trip->id,
                'status'           => BookingStatusEnum::EN_PAIEMENT,
                'comment'          => $data['comment'] ?? null,

                // Destinataire obligatoire
                'recipient_name'   => $data['recipient_name'],
                'recipient_phone'  => $data['recipient_phone'],
                'recipient_email'  => $data['recipient_email'],

                // payment_expires_at calculé dans transitionTo,
                // mais ici on crée directement EN_PAIEMENT donc on le set manuellement
                'payment_expires_at' => now()->addMinutes(
                    config('gpvalise.payment_expiration_minutes', 30)
                ),
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
                        'items' => "La valise {$luggage->id} n'est plus disponible.",
                    ]);
                }

                $booking->bookingItems()->create([
                    'trip_id'     => $trip->id,
                    'luggage_id'  => $luggage->id,
                    'kg_reserved' => $itemData['kg_reserved'],
                    'price'       => $this->calculatePrice($trip, $luggage, $itemData['kg_reserved']),
                ]);

                $luggage->update([
                    'status' => LuggageStatusEnum::RESERVEE,
                ]);
            }

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });
    }

    private function validateRecipient(array $data): void
    {
        $missing = array_filter(
            ['recipient_name', 'recipient_phone', 'recipient_email'],
            fn(string $field) => empty($data[$field])
        );

        if (! empty($missing)) {
            throw ValidationException::withMessages(
                array_fill_keys($missing, 'Ce champ destinataire est obligatoire.')
            );
        }

        if (! filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'recipient_email' => 'Email destinataire invalide.',
            ]);
        }
    }

    private function calculatePrice(
        \App\Models\Trip    $trip,
        \App\Models\Luggage $luggage,
        int                 $gramsReserved
    ): int {
        // Base : prix au poids
        $total = (int) round(($gramsReserved / 1000) * $trip->price_per_kg);

        // Supplément : forfait fixe par content_item selon sa catégorie
        // Un content_item = un article. Deux téléphones = deux content_items PHONE.
        foreach ($luggage->getContentItems() as $item) {
            $fee = $trip->categoryFees
                ->first(fn($f) => $f->category->value === $item->category);

            if ($fee) {
                $total += $fee->fee;
            }
        }

        return $total;
    }
}

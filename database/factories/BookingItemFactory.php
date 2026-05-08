<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        $luggage = Luggage::factory()->create([
            'weight_kg' => $this->faker->numberBetween(50, 250), // ← kg×10 : 5kg→25kg
        ]);

        $trip = Trip::factory()->create();

        // kg_reserved en grammes : entre 1kg et le poids max de la valise
        $gramsReserved = $this->faker->numberBetween(1000, $luggage->weight_kg * 100);
        // weight_kg est en kg×10, donc weight_kg×100 = grammes max

        // price_per_kg en centimes : entre 400 et 600 centimes/kg = 4.00€→6.00€
        $pricePerKgCents = $this->faker->numberBetween(400, 600);

        // prix total = (grammes / 1000) × price_per_kg_cents
        $price = (int) round(($gramsReserved / 1000) * $pricePerKgCents);

        return [
            'booking_id'  => Booking::factory(),
            'luggage_id'  => $luggage->id,
            'trip_id'     => $trip->id,
            'kg_reserved' => $gramsReserved, // grammes
            'price'       => $price,         // centimes
        ];
    }

    public function full(): static
    {
        return $this->afterMaking(function (BookingItem $item): void {
            // weight_kg est en kg×10, conversion en grammes : ×100
            $item->kg_reserved = $item->luggage->weight_kg * 100;
            // prix : 5.00€/kg = 500 centimes/kg
            $item->price = (int) round(($item->kg_reserved / 1000) * 500);
        });
    }
}

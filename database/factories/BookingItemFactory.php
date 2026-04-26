<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        $luggage = Luggage::factory()->create([
            'weight_kg' => $this->faker->randomFloat(1, 5, 25),
        ]);

        $trip = Trip::factory()->create();

        $kgReserved = $this->faker->randomFloat(1, 1, $luggage->weight_kg);

        $pricePerKg = 4 + $this->faker->randomFloat(1, 0, 2);
        $price = round($pricePerKg * $kgReserved, 2);

        return [
            'booking_id'   => Booking::factory(),
            'luggage_id'   => $luggage->id,
            'trip_id'      => $trip->id,
            'kg_reserved'  => $kgReserved,
            'price'        => $price,
        ];
    }


    public function full(): static
    {
        return $this->afterMaking(function (BookingItem $item) {
            $item->kg_reserved = $item->luggage->weight_kg;
            $item->price = round($item->kg_reserved * 5, 2);
        });
    }
}

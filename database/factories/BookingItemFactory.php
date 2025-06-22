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
        $trip = Trip::factory()->create(); // ou Trip::factory()
        $luggage = Luggage::factory()->create([
            'weight_kg' => $this->faker->randomFloat(1, 1, 20),
        ]);

        $kgReserved = $this->faker->randomFloat(1, 0.5, $luggage->weight_kg);
        $pricePerKg = 5; // 💡 tarif standard configurable ailleurs
        $price = round($pricePerKg * $kgReserved, 2);

        return [
            'booking_id' => Booking::factory(),
            'luggage_id' => $luggage->id,
            'trip_id'    => $trip->id,
            'kg_reserved' => $kgReserved,
            'price'      => $price,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(BookingStatusEnum::cases());

        $timestamps = [
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];

        match ($status) {
            BookingStatusEnum::CONFIRMEE => $timestamps['confirmed_at'] = now(),
            BookingStatusEnum::LIVREE,
            BookingStatusEnum::TERMINE => [
                $timestamps['confirmed_at'] = now()->subDays(2),
                $timestamps['completed_at'] = now()
            ],
            BookingStatusEnum::ANNULE,
            BookingStatusEnum::REMBOURSEE => $timestamps['cancelled_at'] = now()->subDays(1),
            default => null
        };

        return [
            'user_id'      => User::factory()->state(['role' => 'expeditor']),
            'trip_id'      => Trip::factory(),
            'status'       => $status,
            'comment'      => $this->faker->optional()->sentence(),
            ...$timestamps,
        ];
    }

    /**
     * 📦 État helper pour booking confirmé
     */
    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::CONFIRMEE,
            'confirmed_at' => now(),
        ]);
    }
}

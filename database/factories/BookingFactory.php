<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Enums\BookingStatusEnum;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(BookingStatusEnum::cases());

        return [
            'user_id'        => User::factory()->state(['role' => 'expeditor']),
            'trip_id'        => Trip::factory(),
            'status'         => $status,
            'comment'        => $this->faker->optional()->sentence(),
            'confirmed_at'   => in_array($status, [BookingStatusEnum::CONFIRMEE, BookingStatusEnum::LIVREE, BookingStatusEnum::TERMINE]) ? now() : null,
            'completed_at'   => in_array($status, [BookingStatusEnum::TERMINE]) ? now()->addDays(3) : null,
            'cancelled_at'   => in_array($status, [BookingStatusEnum::ANNULE, BookingStatusEnum::REMBOURSEE]) ? now()->subDays(2) : null,
        ];
    }
}

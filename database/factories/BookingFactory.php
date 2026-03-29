<?php

namespace Database\Factories;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
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
            'expired_at' => null,
            'payment_expires_at' => null,
        ];

        match ($status) {
            BookingStatusEnum::EN_PAIEMENT => $timestamps['payment_expires_at'] = now()->addMinutes(15),

            BookingStatusEnum::CONFIRMEE => $timestamps['confirmed_at'] = now(),

            BookingStatusEnum::LIVREE => [
                $timestamps['confirmed_at'] = now()->subDays(2),
                $timestamps['completed_at'] = now()->subHour(),
            ],

            BookingStatusEnum::TERMINE => [
                $timestamps['confirmed_at'] = now()->subDays(2),
                $timestamps['completed_at'] = now(),
            ],

            BookingStatusEnum::ANNULE,
            BookingStatusEnum::REMBOURSEE => $timestamps['cancelled_at'] = now()->subDay(),

            BookingStatusEnum::EXPIREE => $timestamps['expired_at'] = now()->subMinutes(5),

            default => null,
        };

        return [
            'user_id' => User::factory(),
            'trip_id' => Trip::factory(),
            'status' => $status,
            'comment' => $this->faker->optional()->sentence(),
            ...$timestamps,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::CONFIRMEE,
            'confirmed_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'expired_at' => null,
            'payment_expires_at' => null,
        ]);
    }

    public function pendingPayment(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::EN_PAIEMENT,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'expired_at' => null,
            'payment_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function expiredPayment(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::EN_PAIEMENT,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'expired_at' => null,
            'payment_expires_at' => now()->subMinutes(15),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::EXPIREE,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'expired_at' => now()->subMinutes(5),
            'payment_expires_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::ANNULE,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => now()->subDay(),
            'expired_at' => null,
            'payment_expires_at' => null,
        ]);
    }
}

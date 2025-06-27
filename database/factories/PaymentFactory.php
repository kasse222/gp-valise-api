<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(PaymentStatusEnum::cases());
        $paidAt = $status === PaymentStatusEnum::SUCCES
            ? $this->faker->dateTimeBetween('-2 months', 'now')
            : null;

        return [
            'user_id'    => User::factory(),
            'booking_id' => Booking::factory(),
            'amount'     => $this->faker->randomFloat(2, 10, 500),
            'method'     => $this->faker->randomElement(PaymentMethodEnum::cases()),
            'status'     => $status,
            'paid_at'    => $paidAt,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | États nommés : pour les tests ciblés
    |--------------------------------------------------------------------------
    */

    public function paid(): static
    {
        return $this->state(fn() => [
            'status'  => PaymentStatusEnum::SUCCES,
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status'  => PaymentStatusEnum::EN_COURS,
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => [
            'status'  => PaymentStatusEnum::ECHEC,
            'paid_at' => null,
        ]);
    }
}

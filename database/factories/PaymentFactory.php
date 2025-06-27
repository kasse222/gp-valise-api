<?php

namespace Database\Factories;

use App\Enums\CurrencyEnum;
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
        $method = $this->faker->randomElement(PaymentMethodEnum::cases());
        $status = $this->faker->randomElement(PaymentStatusEnum::cases());

        return [
            'user_id'           => User::factory(),
            'booking_id'        => Booking::factory(),
            'amount'            => $this->faker->randomFloat(2, 10, 500),
            'method'            => $method->value,
            'status'            => $status->value,
            'currency'          => fake()->randomElement(CurrencyEnum::values()),
            'payment_reference' => $this->faker->uuid(),
            'paid_at'           => $status->isSuccess() ? $this->faker->dateTimeBetween('-2 months', 'now') : null,
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

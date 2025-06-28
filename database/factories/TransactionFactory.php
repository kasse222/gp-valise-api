<?php

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Booking;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(TransactionStatusEnum::cases());
        $method = $this->faker->randomElement(PaymentMethodEnum::cases());

        return [
            'user_id'      => User::factory(),
            'booking_id'   => Booking::factory(),
            'amount'       => $this->faker->randomFloat(2, 10, 500),
            'currency' => CurrencyEnum::default()->value,
            'status'       => $status,
            'method'       => $method,
            'processed_at' => $status === TransactionStatusEnum::PENDING ? null : now(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | États nommés (fixtures ciblées)
    |--------------------------------------------------------------------------
    */

    public function pending(): static
    {
        return $this->state(fn() => [
            'status'       => TransactionStatusEnum::PENDING,
            'processed_at' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn() => [
            'status'       => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn() => [
            'status'       => TransactionStatusEnum::FAILED,
            'processed_at' => now()->subHours(2),
        ]);
    }
}

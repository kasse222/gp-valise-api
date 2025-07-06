<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Booking;
use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $status = fake()->randomElement(TransactionStatusEnum::cases());
        $method = fake()->randomElement(PaymentMethodEnum::cases());

        return [
            'user_id'      => User::factory(),
            'booking_id'   => Booking::factory(),
            'amount'       => fake()->randomFloat(2, 10, 500),
            'currency'     => CurrencyEnum::default(),             // ⬅️ passe bien l’instance Enum
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

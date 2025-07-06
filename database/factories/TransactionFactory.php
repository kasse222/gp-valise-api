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
        return [
            'user_id'      => User::factory(),
            'booking_id'   => Booking::factory(),
            'amount'       => fake()->randomFloat(2, 10, 300),
            'currency'     => CurrencyEnum::EUR->value,
            'status'       => TransactionStatusEnum::PENDING->value,
            'method'       => PaymentMethodEnum::CARTE_BANCAIRE->value,
            'processed_at' => now(),
        ];
    }

    /**
     * Génère une transaction liée explicitement à un user et son booking.
     */
    public function forUserWithBooking(User $user): self
    {
        return $this->state([
            'user_id'    => $user->id,
            'booking_id' => Booking::factory()->for($user),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::PENDING->value,
            'processed_at' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::COMPLETED->value,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::FAILED->value,
            'processed_at' => now()->subHours(2),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Booking;
use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'booking_id' => Booking::factory(),

            'type'   => TransactionTypeEnum::CHARGE->value,
            'amount' => fake()->randomFloat(2, 10, 300),

            'currency' => CurrencyEnum::EUR->value,
            'method'   => PaymentMethodEnum::CARTE_BANCAIRE->value,

            // 🔥 cohérence métier
            'status'       => TransactionStatusEnum::PENDING->value,
            'processed_at' => null,

            // 🔥 essentiel pour webhook
            'provider_transaction_id' => 'fake_' . Str::uuid(),
        ];
    }

    /**
     * 🔗 Lier à un user + booking cohérent
     */
    public function forUserWithBooking(User $user): self
    {
        return $this->state([
            'user_id'    => $user->id,
            'booking_id' => Booking::factory()->for($user),
        ]);
    }

    /**
     * ⏳ Pending
     */
    public function pending(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::PENDING->value,
            'processed_at' => null,
        ]);
    }

    /**
     * ✅ Completed
     */
    public function success(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::COMPLETED->value,
            'processed_at' => now(),
        ]);
    }

    /**
     * ❌ Failed
     */
    public function failed(): static
    {
        return $this->state([
            'status'       => TransactionStatusEnum::FAILED->value,
            'processed_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * 💳 Charge
     */
    public function charge(): static
    {
        return $this->state([
            'type' => TransactionTypeEnum::CHARGE->value,
        ]);
    }

    /**
     * 💸 Refund
     */
    public function refund(): static
    {
        return $this->state([
            'type' => TransactionTypeEnum::REFUND->value,
        ]);
    }

    /**
     * 💼 Payout (future-proof)
     */
    public function payout(): static
    {
        return $this->state([
            'type' => TransactionTypeEnum::PAYOUT->value,
        ]);
    }
}

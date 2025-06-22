<?php

namespace Database\Factories;

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
            'user_id'       => User::factory(),
            'booking_id'    => Booking::factory(),
            'amount'        => $this->faker->randomFloat(2, 10, 500),
            'currency'      => $this->faker->randomElement(['EUR', 'USD', 'XOF']),
            'status'        => $status->value,
            'method'        => $method->value,
            'processed_at'  => $status === TransactionStatusEnum::PENDING ? null : now(),
        ];
    }
}

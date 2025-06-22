<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\Booking;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $methods = PaymentMethodEnum::values();
        $statuses = ['pending', 'paid', 'failed']; // à remplacer par Enum si dispo

        return [
            'user_id'    => User::factory(),
            'booking_id' => Booking::factory(),
            'amount'     => $this->faker->randomFloat(2, 10, 500),
            'method'     => $this->faker->randomElement($methods),
            'status'     => $this->faker->randomElement($statuses),
            'paid_at'    => $this->faker->optional()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}

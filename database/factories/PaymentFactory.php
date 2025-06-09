<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomFloat(2, 10, 200), // entre 10 et 200 €
            'status' => fake()->randomElement(['en_attente', 'paye']),
            'provider' => fake()->randomElement(['Stripe', 'PayPal', 'Virement']),
            'reference' => strtoupper(fake()->bothify('REF###??')),
            'paid_at' => now(),
        ];
    }
}

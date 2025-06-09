<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_type' => $this->faker->randomElement(['trip', 'booking', 'user']),
            'target_id' => $this->faker->numberBetween(1, 50),
            'reason' => $this->faker->randomElement(['contenu illicite', 'arnaque', 'propos déplacés']),
            'comment' => $this->faker->sentence(12),
        ];
    }
}

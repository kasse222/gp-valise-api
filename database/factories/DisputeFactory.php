<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DisputeStatusEnum;
use App\Models\Dispute;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        return [
            'status'      => DisputeStatusEnum::OPEN,
            'reason'      => $this->faker->sentence(10),
            'resolution'  => null,
            'decision'    => null,
            'assigned_to' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'status'      => DisputeStatusEnum::RESOLVED,
            'resolution'  => $this->faker->sentence(),
            'resolved_at' => now(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state([
            'status' => DisputeStatusEnum::UNDER_REVIEW,
        ]);
    }
}

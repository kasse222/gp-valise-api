<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycRequestFactory extends Factory
{
    protected $model = KycRequest::class;

    public function definition(): array
    {
        return [
            'status'             => KycStatusEnum::PENDING,
            'id_photo_path'      => 'kyc/id_' . $this->faker->uuid() . '.jpg',
            'parcel_photo_path'  => 'kyc/parcel_' . $this->faker->uuid() . '.jpg',
            'admin_notes'        => null,
            'rejection_reason'   => null,
            'reviewed_by'        => null,
            'submitted_at'       => now(),
            'reviewed_at'        => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => KycStatusEnum::APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'           => KycStatusEnum::REJECTED,
            'reviewed_at'      => now(),
            'rejection_reason' => 'Documents invalides',
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingStatusHistoryFactory extends Factory
{
    protected $model = BookingStatusHistory::class;

    public function definition(): array
    {
        $oldStatus = $this->faker->randomElement([
            BookingStatusEnum::EN_ATTENTE,
            BookingStatusEnum::EN_PAIEMENT,
            BookingStatusEnum::ACCEPTE,
        ]);

        $newStatus = $this->faker->randomElement(
            array_filter(
                BookingStatusEnum::cases(),
                fn($status) =>
                $status !== $oldStatus && $oldStatus->canTransitionTo($status)
            )
        );

        return [
            'booking_id'   => Booking::factory(),
            'old_status'   => $oldStatus->value,
            'new_status'   => $newStatus->value,
            'changed_by'   => User::factory(),
            'reason'       => $this->faker->sentence(),
        ];
    }
}

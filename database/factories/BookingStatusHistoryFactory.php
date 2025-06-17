<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use App\Status\BookingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingStatusHistory>
 */
class BookingStatusHistoryFactory extends Factory
{
    protected $model = BookingStatusHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldStatus = $this->faker->randomElement([
            BookingStatus::EN_ATTENTE->value,
            BookingStatus::ACCEPTE->value,
            BookingStatus::REFUSE->value,
        ]);

        $newStatus = $this->faker->randomElement(array_diff(
            BookingStatus::cases(),
            [$oldStatus]
        ));

        return [
            'booking_id' => Booking::factory(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value,
            'changed_by' => User::factory(),
            'changed_at' => now()->subMinutes(rand(1, 60)),
        ];
    }
}

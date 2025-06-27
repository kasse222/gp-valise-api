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
        // 💡 Statut initial réaliste
        $oldStatus = $this->faker->randomElement([
            BookingStatusEnum::EN_ATTENTE,
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::EN_PAIEMENT,
        ]);

        // ✅ On filtre les transitions valides uniquement
        $possibleNewStatuses = array_filter(
            BookingStatusEnum::cases(),
            fn(BookingStatusEnum $status) =>
            $status !== $oldStatus && $oldStatus->canTransitionTo($status)
        );

        $newStatus = $this->faker->randomElement($possibleNewStatuses);

        return [
            'booking_id' => Booking::factory(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => User::factory(),
            'reason'     => $this->faker->realText(30),
        ];
    }

    /**
     * 🔄 Historique initial à la création de la réservation
     */
    public function initial(BookingStatusEnum $status = BookingStatusEnum::EN_ATTENTE): static
    {
        return $this->state(fn() => [
            'old_status' => null,
            'new_status' => $status,
            'reason'     => 'Création de la réservation',
        ]);
    }
}

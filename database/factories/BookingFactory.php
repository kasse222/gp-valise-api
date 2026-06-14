<?php

namespace Database\Factories;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        // Exclure les états legacy du random pour les nouveaux tests
        $status = $this->faker->randomElement([
            BookingStatusEnum::EN_PAIEMENT,
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::EN_TRANSIT,
            BookingStatusEnum::LIVREE,
            BookingStatusEnum::TERMINE,
            BookingStatusEnum::ANNULE,
            BookingStatusEnum::EXPIREE,
            BookingStatusEnum::REMBOURSEE,
        ]);

        $timestamps = [
            'confirmed_at'      => null,
            'completed_at'      => null,
            'cancelled_at'      => null,
            'expired_at'        => null,
            'payment_expires_at' => null,
            'handed_over_at'    => null,
            'delivered_at'      => null,
            'escrow_releasable_at' => null,
            'disputed_at'       => null,
        ];

        switch ($status) {
            case BookingStatusEnum::EN_PAIEMENT:
                $timestamps['payment_expires_at'] = now()->addMinutes(15);
                break;

            case BookingStatusEnum::CONFIRMEE:
                $timestamps['confirmed_at'] = now();
                break;

            case BookingStatusEnum::EN_TRANSIT:
                $timestamps['confirmed_at']   = now()->subHours(3);
                $timestamps['handed_over_at'] = now()->subHour();
                break;

            case BookingStatusEnum::LIVREE:
                $timestamps['confirmed_at']         = now()->subDays(2);
                $timestamps['handed_over_at']       = now()->subDays(2)->addHours(2);
                $timestamps['delivered_at']         = now()->subHour();
                $timestamps['escrow_releasable_at'] = now()->addHours(47);
                break;

            case BookingStatusEnum::TERMINE:
                $timestamps['confirmed_at']         = now()->subDays(3);
                $timestamps['handed_over_at']       = now()->subDays(3)->addHours(2);
                $timestamps['delivered_at']         = now()->subDays(2);
                $timestamps['escrow_releasable_at'] = now()->subDay();
                $timestamps['completed_at']         = now();
                break;

            case BookingStatusEnum::ANNULE:
            case BookingStatusEnum::REMBOURSEE:
                $timestamps['cancelled_at'] = now()->subDay();
                break;

            case BookingStatusEnum::EXPIREE:
                $timestamps['expired_at'] = now()->subMinutes(5);
                break;
        }

        return [
            'user_id'          => User::factory(),
            'trip_id'          => Trip::factory(),
            'status'           => $status->value,
            'comment'          => $this->faker->optional()->sentence(),
            'recipient_name'   => $this->faker->name(),
            'recipient_phone'  => $this->faker->phoneNumber(),
            'recipient_email'  => $this->faker->safeEmail(),
            ...$timestamps,
        ];
    }

    // ── Named states ──────────────────────────────────────────────────────────

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status'           => BookingStatusEnum::CONFIRMEE->value,
            'confirmed_at'     => now(),
            'payment_expires_at' => null,
        ]);
    }

    public function pendingPayment(): static
    {
        return $this->state(fn() => [
            'status'             => BookingStatusEnum::EN_PAIEMENT->value,
            'payment_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function expiredPayment(): static
    {
        return $this->state(fn() => [
            'status'             => BookingStatusEnum::EN_PAIEMENT->value,
            'payment_expires_at' => now()->subMinutes(15),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status'     => BookingStatusEnum::EXPIREE->value,
            'expired_at' => now()->subMinutes(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status'        => BookingStatusEnum::ANNULE->value,
            'cancelled_at'  => now()->subDay(),
            'cancel_reason' => 'Annulation par l\'expéditeur',
            'refund_rate'   => 100,
        ]);
    }

    public function enTransit(): static
    {
        return $this->state(fn() => [
            'status'            => BookingStatusEnum::EN_TRANSIT->value,
            'confirmed_at'      => now()->subHours(3),
            'handed_over_at'    => now()->subHour(),
            'delivery_code'     => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'delivery_qr_token' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    public function livree(): static
    {
        return $this->state(fn() => [
            'status'               => BookingStatusEnum::LIVREE->value,
            'confirmed_at'         => now()->subDays(2),
            'handed_over_at'       => now()->subDays(2)->addHours(2),
            'delivered_at'         => now()->subHour(),
            'escrow_releasable_at' => now()->addHours(47),
            'disputed_at'          => null,
        ]);
    }

    public function enLitige(): static
    {
        return $this->state(fn() => [
            'status'       => BookingStatusEnum::EN_LITIGE->value,
            'confirmed_at' => now()->subDays(2),
            'disputed_at'  => now(),
        ]);
    }
}

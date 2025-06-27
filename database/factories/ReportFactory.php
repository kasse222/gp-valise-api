<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Luggage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $reportables = [
            Booking::class,
            Trip::class,
            Luggage::class,
        ];

        /** @var class-string<Model> $reportableType */
        $reportableType = $this->faker->randomElement($reportables);

        /** @var Model $reportable */
        $reportable = $reportableType::factory()->create();

        return [
            'user_id'         => User::factory(),
            'reportable_id'   => $reportable->getKey(),
            'reportable_type' => $reportableType,
            'reason'          => $this->faker->randomElement([
                'contenu inapproprié',
                'arnaque suspectée',
                'informations fausses',
                'communication agressive',
            ]),
            'details'         => $this->faker->realText(150),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | États spécialisés : BookingReport, LuggageReport, etc.
    |--------------------------------------------------------------------------
    */

    public function forBooking(): static
    {
        return $this->state(function () {
            $booking = Booking::factory()->create();
            return [
                'reportable_id'   => $booking->getKey(),
                'reportable_type' => Booking::class,
            ];
        });
    }

    public function forTrip(): static
    {
        return $this->state(function () {
            $trip = Trip::factory()->create();
            return [
                'reportable_id'   => $trip->getKey(),
                'reportable_type' => Trip::class,
            ];
        });
    }

    public function forLuggage(): static
    {
        return $this->state(function () {
            $luggage = Luggage::factory()->create();
            return [
                'reportable_id'   => $luggage->getKey(),
                'reportable_type' => Luggage::class,
            ];
        });
    }
}

<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Models\Trip;
use App\Enums\TripStatusEnum;
use App\Actions\Booking\CanBeReserved;
use Illuminate\Support\Carbon;

use function Pest\Laravel\mock;

it('retourne false si kgDisponible est 0', function () {
    $trip = new class extends Trip {
        public function kgDisponible(): float
        {
            return 0.0;
        }
    };

    $trip->date = now()->addDays(2);
    $trip->status = TripStatusEnum::ACTIVE;

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne false si la date est passée', function () {
    $trip = Trip::factory()->make([
        'date' => now()->subDay(),
        'status' => TripStatusEnum::ACTIVE,
    ]);

    $trip->kgDisponible = fn() => 10;

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne false si le statut n’est pas réservable', function () {
    $trip = Trip::factory()->make([
        'date' => now()->addDays(3),
        'status' => TripStatusEnum::CANCELLED, // ex : pas réservable
    ]);

    $trip->kgDisponible = fn(): float => 10.0;

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne true si tout est ok', function () {
    $trip = Trip::factory()->make([
        'date' => now()->addDay(),
        'status' => TripStatusEnum::ACTIVE,
    ]);

    $trip->kgDisponible = fn() => 20;

    expect(CanBeReserved::handle($trip))->toBeTrue();
});

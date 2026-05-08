<?php

declare(strict_types=1);

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\Booking\CanBeReserved;
use App\Enums\TripStatusEnum;
use App\Models\Trip;

it('retourne false si kgDisponible est 0', function (): void {
    $trip = new class extends Trip {
        public function gramsDisponible(): int // ← kgDisponible → gramsDisponible
        {
            return 0;
        }
    };

    $trip->date   = now()->addDays(2);
    $trip->status = TripStatusEnum::ACTIVE;

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne false si la date est passée', function (): void {
    $trip = Trip::factory()->make([
        'date'   => now()->subDay(),
        'status' => TripStatusEnum::ACTIVE,
    ]);

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne false si le statut n\'est pas réservable', function (): void {
    $trip = Trip::factory()->make([
        'date'   => now()->addDays(3),
        'status' => TripStatusEnum::CANCELLED,
    ]);

    expect(CanBeReserved::handle($trip))->toBeFalse();
});

it('retourne true si tout est ok', function (): void {
    $trip = Trip::factory()->make([
        'date'   => now()->addDay(),
        'status' => TripStatusEnum::ACTIVE,
        'capacity' => 50000, // ← grammes : 50kg, gramsDisponible() retournera > 0
    ]);

    expect(CanBeReserved::handle($trip))->toBeTrue();
});

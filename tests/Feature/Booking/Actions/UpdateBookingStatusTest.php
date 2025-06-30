<?php

use App\Actions\Booking\UpdateBookingStatus;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);
beforeEach(function () {
    $this->expediteur = User::factory()->expeditor()->create();
});
it('met à jour le statut si autorisé', function () {
    $voyageur = User::factory()->create();   // celui qui possède le trip
    $expediteur = User::factory()->create(); // celui qui réserve

    $trip = Trip::factory()->for($voyageur)->create();

    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::EN_ATTENTE,
        'user_id' => $expediteur->id, // <- c’est bien l’expéditeur
        'trip_id' => $trip->id,       // <- et le trip appartient au voyageur
    ]);

    $booking->refresh();

    $updated = UpdateBookingStatus::execute(
        $booking,
        BookingStatusEnum::CONFIRMEE,
        $voyageur // <- seul le voyageur est autorisé ici
    );

    expect($updated->status)->toBe(BookingStatusEnum::CONFIRMEE);
});

it('rejette une transition non autorisée', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::LIVREE,
        'user_id' => $this->expediteur->id,
    ]);

    UpdateBookingStatus::execute($booking, BookingStatusEnum::CONFIRMEE, $this->expediteur);
})->throws(HttpException::class, 'Transition de statut non autorisée.');

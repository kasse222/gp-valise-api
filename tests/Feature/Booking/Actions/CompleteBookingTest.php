<?php

use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('livre une réservation avec succès', function () {
    // 🧪 Création du voyageur (celui qui possède le trip)
    $voyageur = User::factory()->create();

    // 👤 Création du trip associé à ce voyageur
    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    // 👤 Création de l’expéditeur
    $expediteur = User::factory()->create();

    // 📦 Création de la réservation dans l’état CONFIRMEE
    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    // 🔐 Authentification en tant que voyageur
    Sanctum::actingAs($voyageur);

    // ✅ Appel de l’action métier
    $result = (new CompleteBooking())->execute($booking);

    // 🔍 On recharge les relations pour assertions propres
    $result->load('statusHistories');

    // ✅ Assertions
    expect($result)
        ->status->toBe(BookingStatusEnum::LIVREE)
        ->statusHistories->last()->new_status->toBe(BookingStatusEnum::LIVREE);
});

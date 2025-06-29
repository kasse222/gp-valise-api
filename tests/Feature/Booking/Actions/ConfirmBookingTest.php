<?php

use App\Actions\Booking\ConfirmBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Trip;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('confirme une réservation avec succès si la capacité le permet', function () {
    // 🧑 Voyageur (propriétaire du trip)
    $voyageur = User::factory()->create();

    // 📦 Trajet avec 100 kg dispo
    $trip = Trip::factory()->for($voyageur)->create([
        'capacity' => 100,
    ]);

    // 👤 Réservataire (expéditeur)
    $expediteur = User::factory()->create();

    // 🧳 Réservation en attente avec 20kg réservés
    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::EN_ATTENTE,
        ]);

    BookingItem::factory()->for($booking)->create([
        'kg_reserved' => 20,
    ]);

    // 🎭 Authentification en tant que voyageur
    Sanctum::actingAs($voyageur);

    // 🚀 Exécution de la logique métier
    $result = (new ConfirmBooking())->execute($booking->id);

    // ✅ Vérifications
    expect($result->confirmed_at)->not()->toBeNull();
    expect($result->cancelled_at)->toBeNull();
});

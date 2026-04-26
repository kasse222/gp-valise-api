<?php

use App\Actions\Booking\GetUserBookings;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('retourne les réservations liées aux trajets du voyageur', function () {
    Booking::disableAutoStatusCreation();

    $voyageur = User::factory()->traveler()->create();

    $trip = Trip::factory()->for($voyageur)->create();

    $expediteur = User::factory()->sender()->create();
    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create();

    $luggage = Luggage::factory()->for($expediteur)->create();
    BookingItem::factory()->for($booking)->create(['luggage_id' => $luggage->id]);

    BookingStatusHistory::factory()->for($booking)->create();

    $result = app(GetUserBookings::class)->execute($voyageur);


    $target = $result->firstWhere('id', $booking->id);


    expect($result->pluck('id'))->toContain($booking->id);
    expect($target)->not->toBeNull();
    expect($target->trip->user_id)->toBe($voyageur->id);
    expect($target->bookingItems->first()->luggage_id)->toBe($luggage->id);
    expect($target->statusHistories)->toHaveCount(1);
});

it('retourne les propres réservations de l’expéditeur', function () {
    Booking::disableAutoStatusCreation();

    $expediteur = User::factory()->sender()->create();
    $trip = Trip::factory()->create();


    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create();

    $luggage = Luggage::factory()->for($expediteur)->create();
    BookingItem::factory()->for($booking)->create(['luggage_id' => $luggage->id]);

    BookingStatusHistory::factory()->for($booking)->create();

    $result = app(GetUserBookings::class)->execute($expediteur);

    $target = $result->firstWhere('id', $booking->id);



    expect($result->pluck('id'))->toContain($booking->id);
    expect($target)->not->toBeNull();
    expect($target->user_id)->toBe($expediteur->id);
    expect($target->bookingItems->first()->luggage_id)->toBe($luggage->id);
    expect($target->statusHistories)->toHaveCount(1);
});

it('retourne une liste vide si aucun booking', function () {
    $user = User::factory()->sender()->create();

    $result = app(GetUserBookings::class)->execute($user);
    expect($result)->toBeEmpty();
});

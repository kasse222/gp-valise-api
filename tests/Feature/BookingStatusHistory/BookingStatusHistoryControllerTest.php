<?php

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;

use function Pest\Laravel\actingAs;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);
// 🧱 Setup commun
beforeEach(function () {
    $this->user = User::factory()->verified()->sender()->create();
    $this->booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'status' => BookingStatusEnum::ACCEPTE,
    ]);
    actingAs($this->user);
});

it('liste les historiques de statuts d’un booking', function () {
    BookingStatusHistory::factory()->count(3)->create([
        'booking_id' => $this->booking->id,
        'old_status' => BookingStatusEnum::EN_PAIEMENT,
        'new_status' => BookingStatusEnum::ACCEPTE,
    ]);

    $response = $this->getJson(route('api.v1.bookings.status_histories.index', $this->booking));

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment(['new_status' => BookingStatusEnum::ACCEPTE->value]);
});

it('crée un historique de statut pour un booking', function () {
    $data = [
        'old_status' => BookingStatusEnum::ACCEPTE->value,
        'new_status' => BookingStatusEnum::CONFIRMEE->value,
        'comment' => 'Passage à confirmé.',
    ];

    $response = $this->postJson(route('api.v1.bookings.status_histories.store', $this->booking), $data);

    $response->assertCreated()
        ->assertJsonPath('data.old_status', BookingStatusEnum::ACCEPTE->value)
        ->assertJsonPath('data.new_status', BookingStatusEnum::CONFIRMEE->value);

    expect(BookingStatusHistory::count())->toBe(1);
});

it('refuse la création si l’utilisateur n’est pas le propriétaire', function () {
    // 👤 Création de l'utilisateur propriétaire de la réservation
    $owner = User::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $owner->id]);

    // 🚫 Création d’un autre utilisateur NON propriétaire
    $intruder = User::factory()->create();

    // 🔐 Authentification en tant qu'intrus
    /** @var \App\Models\User $intruder */
    actingAs($intruder);

    // 🔁 Tentative de création d’un historique de statut
    $response = $this->postJson(route('api.v1.bookings.status_histories.store', $booking), [
        'old_status' => BookingStatusEnum::ACCEPTE->value,
        'new_status' => BookingStatusEnum::CONFIRMEE->value,
        'reason'     => 'Tentative non autorisée',
    ]);

    // ✅ Vérification que l’accès est refusé
    $response->assertForbidden();
});

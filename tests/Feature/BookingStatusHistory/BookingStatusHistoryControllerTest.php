<?php

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

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
    // 👤 Création d’un propriétaire et d’un autre utilisateur non autorisé
    $owner = User::factory()->traveler()->create();
    $other = User::factory()->sender()->create();

    // 📦 Réservation appartenant au propriétaire
    $booking = Booking::factory()->create(['user_id' => $owner->id]);

    // 🔐 Authentification en tant qu’utilisateur non autorisé
    actingAs($other);

    $newStatus = BookingStatusEnum::CONFIRMEE;

    // ⚠️ On évite que le new_status soit identique pour ne pas avoir une erreur de validation
    if ($booking->status === $newStatus) {
        $newStatus = BookingStatusEnum::ANNULE;
    }

    // 🚫 Tentative de changement de statut non autorisée
    $response = postJson(route('api.v1.bookings.status.store', $booking), [
        'old_status' => $booking->status->value, // ✅ requis par le FormRequest
        'new_status' => $newStatus->value,
        'reason'     => 'Tentative non autorisée',
    ]);

    // ✅ On vérifie bien que l’accès est refusé (403) et pas une erreur de validation (422)
    $response->assertForbidden();
});

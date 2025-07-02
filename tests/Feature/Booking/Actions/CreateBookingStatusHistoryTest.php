<?php

use App\Models\User;
use App\Models\Booking;
use App\Enums\BookingStatusEnum;
use App\Actions\Booking\CreateBookingStatusHistory;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('crée un historique de statut valide', function () {
    $admin = User::factory()->admin()->create();

    $booking = Booking::factory()->create(['status' => BookingStatusEnum::ACCEPTE]);

    Sanctum::actingAs($admin);

    $history = CreateBookingStatusHistory::execute($booking, [
        'old_status' => BookingStatusEnum::ACCEPTE,
        'new_status' => BookingStatusEnum::CONFIRMEE,
        'reason' => 'Validation manuelle',
    ]);

    expect($history)
        ->old_status->toBe(BookingStatusEnum::ACCEPTE)
        ->new_status->toBe(BookingStatusEnum::CONFIRMEE)
        ->reason->toBe('Validation manuelle');

    assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'new_status' => BookingStatusEnum::CONFIRMEE->value,
    ]);
});

it('rejette un changement vers le même statut', function () {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::ACCEPTE]);

    CreateBookingStatusHistory::execute($booking, [
        'old_status' => BookingStatusEnum::ACCEPTE,
        'new_status' => BookingStatusEnum::CONFIRMEE,
    ]);

    expect(fn() => CreateBookingStatusHistory::execute($booking, [
        'old_status' => BookingStatusEnum::CONFIRMEE,
        'new_status' => BookingStatusEnum::CONFIRMEE,
    ]))->toThrow(ValidationException::class);
});
it('refuse une transition de statut non autorisée', function () {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::LIVREE]);

    $this->actingAs($booking->user);

    $response = $this->postJson(route('api.v1.bookings.status_histories.store', $booking), [
        'old_status' => BookingStatusEnum::LIVREE->value,
        'new_status' => BookingStatusEnum::CONFIRMEE->value, // ❌ retour en arrière interdit
        'reason'     => 'test invalide',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_status']);
});

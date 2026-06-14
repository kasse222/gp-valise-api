<?php

declare(strict_types=1);

use App\Actions\Booking\HandOverBooking;
use App\Enums\BookingStatusEnum;
use App\Events\BookingHandedOver;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->sender   = User::factory()->sender()->verified()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
    ]);
});

it('passe le booking de CONFIRMEE à EN_TRANSIT', function (): void {
    $result = app(HandOverBooking::class)->execute($this->booking, $this->traveler);

    expect($result->status)->toBe(BookingStatusEnum::EN_TRANSIT)
        ->and($result->handed_over_at)->not->toBeNull()
        ->and($result->delivery_code)->toHaveLength(6)
        ->and($result->delivery_qr_token)->not->toBeNull();
});

it('génère un delivery_code à 6 chiffres', function (): void {
    $result = app(HandOverBooking::class)->execute($this->booking, $this->traveler);

    expect($result->delivery_code)->toMatch('/^\d{6}$/');
});

it('génère un delivery_qr_token UUID unique', function (): void {
    $result = app(HandOverBooking::class)->execute($this->booking, $this->traveler);

    expect($result->delivery_qr_token)->toMatch(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
    );
});

it('historise la transition CONFIRMEE → EN_TRANSIT', function (): void {
    $result = app(HandOverBooking::class)->execute($this->booking, $this->traveler);

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $result->id,
        'old_status' => BookingStatusEnum::CONFIRMEE->value,
        'new_status' => BookingStatusEnum::EN_TRANSIT->value,
    ]);
});

it('dispatch BookingHandedOver', function (): void {
    Event::fake();

    // Booking créé APRÈS Event::fake() pour éviter que beforeEach soit intercepté
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
    ]);

    $result = app(HandOverBooking::class)->execute($booking, $this->traveler);

    Event::assertDispatched(BookingHandedOver::class, function (BookingHandedOver $e) use ($result): bool {
        return $e->booking->id === $result->id
            && $e->booking->status === BookingStatusEnum::EN_TRANSIT;
    });
});

it('refuse si acteur n\'est pas le voyageur du trip', function (): void {
    $autreUser = User::factory()->traveler()->create();

    expect(fn() => app(HandOverBooking::class)->execute($this->booking, $autreUser))
        ->toThrow(ValidationException::class);
});

it('refuse si booking pas en CONFIRMEE', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::EN_PAIEMENT]);

    expect(fn() => app(HandOverBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

it('refuse si booking déjà EN_TRANSIT', function (): void {
    $this->booking->update([
        'status'            => BookingStatusEnum::EN_TRANSIT,
        'handed_over_at'    => now(),
        'delivery_code'     => '123456',
        'delivery_qr_token' => 'some-uuid',
    ]);

    expect(fn() => app(HandOverBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

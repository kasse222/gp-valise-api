<?php

declare(strict_types=1);

use App\Actions\Booking\DeclineBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->sender   = User::factory()->sender()->verified()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status'  => BookingStatusEnum::PENDING_APPROVAL,
    ]);
});

it('le voyageur peut refuser une réservation en attente', function (): void {
    $booking = app(DeclineBooking::class)->execute($this->booking, $this->traveler);

    expect($booking->status)->toBe(BookingStatusEnum::DECLINED_BY_TRAVELER)
        ->and($booking->declined_at)->not->toBeNull();

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'old_status' => BookingStatusEnum::PENDING_APPROVAL->value,
        'new_status' => BookingStatusEnum::DECLINED_BY_TRAVELER->value,
    ]);
});

it('libère les bagages après refus', function (): void {
    $luggage = Luggage::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'trip_id'    => $this->trip->id,
        'luggage_id' => $luggage->id,
    ]);

    app(DeclineBooking::class)->execute($this->booking, $this->traveler);

    $luggage->refresh();
    expect($luggage->status)->toBe(LuggageStatusEnum::EN_ATTENTE);
});

it('refuse le déclin si l\'acteur n\'est pas le voyageur du trajet', function (): void {
    $autreUser = User::factory()->traveler()->create();

    expect(fn() => app(DeclineBooking::class)->execute($this->booking, $autreUser))
        ->toThrow(ValidationException::class);
});

it('refuse le déclin si le booking n\'est pas en PENDING_APPROVAL', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::EN_PAIEMENT]);

    expect(fn() => app(DeclineBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

it('refuse le déclin si le booking est final', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::ANNULE]);

    expect(fn() => app(DeclineBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

it('DECLINED_BY_TRAVELER est un statut final', function (): void {
    $booking = app(DeclineBooking::class)->execute($this->booking, $this->traveler);

    expect($booking->isFinal())->toBeTrue();
});

it('dispatch l\'event BookingDeclined', function (): void {
    \Illuminate\Support\Facades\Event::fake();

    app(DeclineBooking::class)->execute($this->booking, $this->traveler);

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\BookingDeclined::class);
});

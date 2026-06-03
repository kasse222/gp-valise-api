<?php

declare(strict_types=1);

use App\Actions\Booking\ApproveBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
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

it('le voyageur peut approuver une réservation en attente', function (): void {
    $booking = app(ApproveBooking::class)->execute($this->booking, $this->traveler);

    expect($booking->status)->toBe(BookingStatusEnum::EN_PAIEMENT)
        ->and($booking->approved_at)->not->toBeNull()
        ->and($booking->payment_expires_at)->not->toBeNull();

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'old_status' => BookingStatusEnum::PENDING_APPROVAL->value,
        'new_status' => BookingStatusEnum::EN_PAIEMENT->value,
    ]);
});

it('refuse l\'approbation si l\'acteur n\'est pas le voyageur du trajet', function (): void {
    $autreUser = User::factory()->traveler()->create();

    expect(fn() => app(ApproveBooking::class)->execute($this->booking, $autreUser))
        ->toThrow(ValidationException::class);
});

it('refuse l\'approbation si le booking n\'est pas en PENDING_APPROVAL', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::EN_PAIEMENT]);

    expect(fn() => app(ApproveBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

it('refuse l\'approbation si le booking est final', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::ANNULE]);

    expect(fn() => app(ApproveBooking::class)->execute($this->booking, $this->traveler))
        ->toThrow(ValidationException::class);
});

it('payment_expires_at est défini après approbation', function (): void {
    $booking = app(ApproveBooking::class)->execute($this->booking, $this->traveler);

    expect($booking->payment_expires_at)->not->toBeNull()
        ->and($booking->payment_expires_at->isFuture())->toBeTrue();
});

it('dispatch l\'event BookingApproved', function (): void {
    \Illuminate\Support\Facades\Event::fake();

    app(ApproveBooking::class)->execute($this->booking, $this->traveler);

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\BookingApproved::class);
});

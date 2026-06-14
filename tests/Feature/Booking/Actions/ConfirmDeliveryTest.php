<?php

declare(strict_types=1);

use App\Actions\Booking\ConfirmDelivery;
use App\Enums\BookingStatusEnum;
use App\Events\BookingDelivered;
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
    $this->booking  = Booking::factory()->enTransit()->create([
        'user_id'            => $this->sender->id,
        'trip_id'            => $this->trip->id,
        'delivery_code'      => '123456',
        'delivery_qr_token'  => 'test-uuid-token',
    ]);
});

it('passe EN_TRANSIT → LIVREE via code secret', function (): void {
    $result = app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, '123456');

    expect($result->status)->toBe(BookingStatusEnum::LIVREE)
        ->and($result->delivered_at)->not->toBeNull()
        ->and($result->escrow_releasable_at)->not->toBeNull()
        ->and($result->escrow_releasable_at->isAfter(now()))->toBeTrue();
});

it('passe EN_TRANSIT → LIVREE via QR token', function (): void {
    $result = app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, 'test-uuid-token');

    expect($result->status)->toBe(BookingStatusEnum::LIVREE);
});

it('escrow_releasable_at = delivered_at + 48h', function (): void {
    $result = app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, '123456');

    // delivered_at < escrow_releasable_at → diff positif dans le bon sens
    $diff = $result->delivered_at->diffInHours($result->escrow_releasable_at);
    expect($diff)->toEqual(48);
});

it('historise la transition EN_TRANSIT → LIVREE', function (): void {
    $result = app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, '123456');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $result->id,
        'old_status' => BookingStatusEnum::EN_TRANSIT->value,
        'new_status' => BookingStatusEnum::LIVREE->value,
    ]);
});

it('dispatch BookingDelivered', function (): void {
    Event::fake();

    $booking = Booking::factory()->enTransit()->create([
        'user_id'            => $this->sender->id,
        'trip_id'            => $this->trip->id,
        'delivery_code'      => '999111',
        'delivery_qr_token'  => 'fresh-uuid-token',
    ]);

    $result = app(ConfirmDelivery::class)->execute($booking, $this->traveler, '999111');

    Event::assertDispatched(BookingDelivered::class, function (BookingDelivered $e) use ($result): bool {
        return $e->booking->id === $result->id
            && $e->booking->status === BookingStatusEnum::LIVREE;
    });
});

it('refuse si code invalide', function (): void {
    expect(fn() => app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, '000000'))
        ->toThrow(ValidationException::class);
});

it('refuse si acteur n\'est pas le voyageur du trip', function (): void {
    $autreUser = User::factory()->traveler()->create();

    expect(fn() => app(ConfirmDelivery::class)->execute($this->booking, $autreUser, '123456'))
        ->toThrow(ValidationException::class);
});

it('refuse si booking pas EN_TRANSIT', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::CONFIRMEE]);

    expect(fn() => app(ConfirmDelivery::class)->execute($this->booking, $this->traveler, '123456'))
        ->toThrow(ValidationException::class);
});

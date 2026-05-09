<?php

declare(strict_types=1);

use App\Actions\Booking\OpenDispute;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Events\BookingDisputed;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->action     = app(OpenDispute::class);
    $this->traveler   = User::factory()->traveler()->verified()->create();
    $this->trip       = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->expediteur = User::factory()->sender()->verified()->create();
});

// ── helpers ──────────────────────────────────────────────────────────────────

function bookingConfirmee(User $expediteur, Trip $trip): Booking
{
    return Booking::factory()->for($expediteur)->for($trip)->create([
        'status'       => BookingStatusEnum::CONFIRMEE,
        'confirmed_at' => now(),
    ]);
}

function bookingLivree(User $expediteur, Trip $trip): Booking
{
    return Booking::factory()->for($expediteur)->for($trip)->create([
        'status'               => BookingStatusEnum::LIVREE,
        'confirmed_at'         => now()->subDays(2),
        'completed_at'         => now()->subHour(),
        'delivered_at'         => now()->subHour(),
        'escrow_releasable_at' => now()->addHours(47),
        'disputed_at'          => null,
    ]);
}

// ── cas nominaux ─────────────────────────────────────────────────────────────

it('expéditeur peut ouvrir une dispute sur un booking CONFIRMEE', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $result = $this->action->execute($booking, $this->expediteur, 'Colis endommagé');

    expect($result->status)->toBe(BookingStatusEnum::EN_LITIGE)
        ->and($result->disputed_at)->not->toBeNull();
});

it('expéditeur peut ouvrir une dispute sur un booking LIVREE', function (): void {
    $booking = bookingLivree($this->expediteur, $this->trip);

    $result = $this->action->execute($booking, $this->expediteur, 'Colis non conforme');

    expect($result->status)->toBe(BookingStatusEnum::EN_LITIGE)
        ->and($result->disputed_at)->not->toBeNull();
});

it('admin peut ouvrir une dispute', function (): void {
    $admin   = User::factory()->admin()->create();
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $result = $this->action->execute($booking, $admin, 'Intervention support');

    expect($result->status)->toBe(BookingStatusEnum::EN_LITIGE)
        ->and($result->disputed_at)->not->toBeNull();
});

it('dispatch BookingDisputed après ouverture', function (): void {
    Event::fake();

    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $result = $this->action->execute($booking, $this->expediteur, 'Litige ouvert');

    Event::assertDispatched(BookingDisputed::class, function (BookingDisputed $event) use ($result): bool {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::EN_LITIGE;
    });
});

it('disputed_at bloque isEscrowReleasable()', function (): void {
    $booking = bookingLivree($this->expediteur, $this->trip);

    // Forcer escrow libérable
    $booking->update(['escrow_releasable_at' => now()->subHour()]);

    expect($booking->fresh()->isEscrowReleasable())->toBeTrue();

    $this->action->execute($booking, $this->expediteur, 'Litige post-escrow');

    expect($booking->fresh()->isEscrowReleasable())->toBeFalse();
});

it('historise la transition EN_LITIGE', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $result = $this->action->execute($booking, $this->expediteur, 'Objet manquant');

    $result->load('statusHistories');

    expect($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::EN_LITIGE)
        ->and($result->statusHistories->last()->reason)->toBe('Objet manquant');
});

// ── cas d'erreur ──────────────────────────────────────────────────────────────

it('voyageur ne peut pas ouvrir une dispute', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->traveler, 'Tentative voyageur');
})->throws(ValidationException::class);

it('refuse si raison vide', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->expediteur, '   ');
})->throws(ValidationException::class);

it('refuse si booking déjà en litige', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);
    $booking->update(['disputed_at' => now()]);

    $this->action->execute($booking, $this->expediteur, 'Double dispute');
})->throws(ValidationException::class);

it('refuse si payout existe déjà', function (): void {
    $booking = bookingLivree($this->expediteur, $this->trip);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::PENDING,
        'amount'     => 9000,
    ]);

    $this->action->execute($booking, $this->expediteur, 'Litige après payout');
})->throws(ValidationException::class);

it('refuse si refund existe déjà', function (): void {
    $booking = bookingConfirmee($this->expediteur, $this->trip);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::REFUND,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
    ]);

    $this->action->execute($booking, $this->expediteur, 'Litige après refund');
})->throws(ValidationException::class);

it('refuse si statut non disputeable — EN_PAIEMENT', function (): void {
    $booking = Booking::factory()->for($this->expediteur)->for($this->trip)->create([
        'status'             => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $this->action->execute($booking, $this->expediteur, 'Trop tôt');
})->throws(ValidationException::class);

it('refuse si statut final — TERMINE', function (): void {
    $booking = Booking::factory()->for($this->expediteur)->for($this->trip)->create([
        'status' => BookingStatusEnum::TERMINE,
    ]);

    $this->action->execute($booking, $this->expediteur, 'Trop tard');
})->throws(ValidationException::class);

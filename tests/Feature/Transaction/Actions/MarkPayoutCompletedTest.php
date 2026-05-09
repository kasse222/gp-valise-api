<?php

declare(strict_types=1);

use App\Actions\Transaction\MarkPayoutCompleted;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Services\LedgerReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);
    $this->action = app(MarkPayoutCompleted::class);
    $this->reader = app(LedgerReader::class);

    $this->traveler   = User::factory()->traveler()->verified()->create();
    $this->expediteur = User::factory()->sender()->verified()->create();
    $this->trip       = Trip::factory()->create(['user_id' => $this->traveler->id]);
});

// ── helpers ───────────────────────────────────────────────────────────────────

function livreedBookingForMarkPayout(User $expediteur, Trip $trip): Booking
{
    return Booking::factory()->for($expediteur)->for($trip)->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now()->subHours(49),
        'escrow_releasable_at' => now()->subHours(1),
        'disputed_at'          => null,
    ]);
}

function pendingPayoutFor(User $traveler, Booking $booking, int $amount = 9000): Transaction
{
    return Transaction::factory()->create([
        'user_id'                 => $traveler->id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::PAYOUT,
        'status'                  => TransactionStatusEnum::PENDING,
        'amount'                  => $amount,
        'currency'                => 'EUR',
        'processed_at'            => null,
        'provider_transaction_id' => 'fake_payout_' . \Illuminate\Support\Str::uuid(),
    ]);
}

// ── cas nominaux ──────────────────────────────────────────────────────────────

it('marque un payout PENDING comme COMPLETED', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);
    $payout  = pendingPayoutFor($this->traveler, $booking);

    $result = $this->action->execute($payout);

    expect($result->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($result->processed_at)->not->toBeNull();
});

it('crée les entries ledger writePayoutPaid', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);
    $payout  = pendingPayoutFor($this->traveler, $booking, 9000);

    $this->action->execute($payout);

    // payable_voyageur_eur DEBIT 9000
    // external_psp_clearing_eur CREDIT 9000
    expect(LedgerEntry::count())->toBe(2)
        ->and($this->reader->isBalanced())->toBeTrue()
        ->and($this->reader->payableVoyageurBalance('eur'))->toBe(-9000) // DEBIT net
        ->and($this->reader->balanceFor('external_psp_clearing_eur'))->toBe(9000); // CREDIT net
});

it('passe le booking LIVREE à TERMINE', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);
    $payout  = pendingPayoutFor($this->traveler, $booking);

    $this->action->execute($payout);

    expect($booking->fresh()->status)->toBe(BookingStatusEnum::TERMINE);
});

it('historise la transition LIVREE → TERMINE', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);
    $payout  = pendingPayoutFor($this->traveler, $booking);

    $this->action->execute($payout);

    $booking->load('statusHistories');

    expect($booking->statusHistories->last()->new_status)->toBe(BookingStatusEnum::TERMINE)
        ->and($booking->statusHistories->last()->reason)->toBe('Payout complété — booking terminé');
});

it('ne transite pas le booking si statut différent de LIVREE', function (): void {
    $booking = Booking::factory()->for($this->expediteur)->for($this->trip)->create([
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);
    $payout = pendingPayoutFor($this->traveler, $booking);

    $this->action->execute($payout);

    expect($booking->fresh()->status)->toBe(BookingStatusEnum::EN_LITIGE); // inchangé
});

// ── cas d'erreur ──────────────────────────────────────────────────────────────

it('refuse si la transaction n\'est pas un payout', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);

    $charge = Transaction::factory()->create([
        'user_id'    => $this->expediteur->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
        'currency'   => 'EUR',
    ]);

    $this->action->execute($charge);
})->throws(ValidationException::class);

it('refuse si le payout est déjà COMPLETED — idempotence stricte', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);

    $payout = Transaction::factory()->create([
        'user_id'                 => $this->traveler->id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::PAYOUT,
        'status'                  => TransactionStatusEnum::COMPLETED,
        'amount'                  => 9000,
        'currency'                => 'EUR',
        'processed_at'            => now()->subMinute(),
        'provider_transaction_id' => 'fake_payout_already_done',
    ]);

    $this->action->execute($payout);
})->throws(ValidationException::class);

it('refuse si le payout est FAILED', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);

    $payout = Transaction::factory()->create([
        'user_id'                 => $this->traveler->id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::PAYOUT,
        'status'                  => TransactionStatusEnum::FAILED,
        'amount'                  => 9000,
        'currency'                => 'EUR',
        'processed_at'            => now()->subMinute(),
        'provider_transaction_id' => 'fake_payout_failed',
    ]);

    $this->action->execute($payout);
})->throws(ValidationException::class);

it('aucune entry ledger si validation échoue', function (): void {
    $booking = livreedBookingForMarkPayout($this->expediteur, $this->trip);

    $charge = Transaction::factory()->create([
        'user_id'    => $this->expediteur->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
        'currency'   => 'EUR',
    ]);

    try {
        $this->action->execute($charge);
    } catch (\Illuminate\Validation\ValidationException) {
    }

    expect(LedgerEntry::count())->toBe(0);
});

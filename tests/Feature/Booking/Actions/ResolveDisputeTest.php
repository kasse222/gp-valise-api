<?php

declare(strict_types=1);

use App\Actions\Booking\ResolveDispute;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);

    $this->admin     = User::factory()->admin()->create();
    $this->traveler  = User::factory()->traveler()->create();
    $this->expediteur = User::factory()->sender()->create();
    $this->trip      = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->action    = app(ResolveDispute::class);
});

// ── helpers ───────────────────────────────────────────────────────────────────

function disputedBookingWithPayout(User $expediteur, Trip $trip): array
{
    $booking = Booking::factory()->for($expediteur)->for($trip)->create([
        'status'               => BookingStatusEnum::EN_LITIGE,
        'delivered_at'         => now()->subHours(50),
        'escrow_releasable_at' => now()->subHours(2),
        'disputed_at'          => now()->subHour(),
    ]);

    $charge = Transaction::factory()->create([
        'user_id'                 => $expediteur->id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::CHARGE,
        'status'                  => TransactionStatusEnum::COMPLETED,
        'amount'                  => 10000,
        'currency'                => 'EUR',
        'processed_at'            => now()->subHours(50),
        'provider_transaction_id' => 'fake_charge_' . \Illuminate\Support\Str::uuid(),
    ]);

    $payout = Transaction::factory()->create([
        'user_id'                 => $booking->trip->user_id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::PAYOUT,
        'status'                  => TransactionStatusEnum::PENDING,
        'amount'                  => 9000,
        'currency'                => 'EUR',
        'provider_transaction_id' => 'fake_payout_' . \Illuminate\Support\Str::uuid(),
    ]);

    $fee = Transaction::factory()->create([
        'user_id'                 => $booking->trip->user_id,
        'booking_id'              => $booking->id,
        'type'                    => TransactionTypeEnum::FEE,
        'status'                  => TransactionStatusEnum::COMPLETED,
        'amount'                  => 1000,
        'currency'                => 'EUR',
        'processed_at'            => now(),
        'provider_transaction_id' => 'fake_fee_' . \Illuminate\Support\Str::uuid(),
    ]);

    return compact('booking', 'charge', 'payout', 'fee');
}

function disputedBookingWithoutPayout(User $expediteur, Trip $trip): Booking
{
    return Booking::factory()->for($expediteur)->for($trip)->create([
        'status'      => BookingStatusEnum::EN_LITIGE,
        'disputed_at' => now()->subHour(),
    ]);
}

// ── decision refund ───────────────────────────────────────────────────────────

it('résolution refund crée une transaction REFUND', function (): void {
    ['booking' => $booking, 'charge' => $charge] = disputedBookingWithPayout(
        $this->expediteur,
        $this->trip
    );

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, 'Colis non reçu');

    expect(
        Transaction::where('booking_id', $booking->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->whereIn('status', [
                TransactionStatusEnum::PENDING,
                TransactionStatusEnum::COMPLETED,
            ])
            ->exists()
    )->toBeTrue();
});

it('résolution refund crée un AuditLog dispute_resolved scellé', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, 'Colis non reçu');

    $log = AuditLog::where('action', 'dispute_resolved')
        ->where('auditable_id', $booking->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['decision'])->toBe('refund')
        ->and($log->metadata['reason'])->toBe('Colis non reçu')
        ->and($log->integrity_hash)->not->toBeNull();
});

it('résolution refund booking reste EN_LITIGE — webhook finalisera', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, 'Colis endommagé');

    // Le booking reste EN_LITIGE jusqu'au webhook refund.completed
    expect($booking->fresh()->status)->toBe(BookingStatusEnum::EN_LITIGE);
});

// ── decision payout ───────────────────────────────────────────────────────────

it('résolution payout passe le booking à TERMINE', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_PAYOUT, 'Litige non fondé');

    expect($booking->fresh()->status)->toBe(BookingStatusEnum::TERMINE);
});

it('résolution payout crée les entries ledger writePayoutPaid', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_PAYOUT, 'Litige non fondé');

    expect(LedgerEntry::count())->toBe(2)
        ->and(app(\App\Services\LedgerReader::class)->isBalanced())->toBeTrue();
});

it('résolution payout crée un AuditLog dispute_resolved scellé', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_PAYOUT, 'Litige non fondé');

    $log = AuditLog::where('action', 'dispute_resolved')
        ->where('auditable_id', $booking->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['decision'])->toBe('payout')
        ->and($log->integrity_hash)->not->toBeNull();
});

it('résolution payout historise la transition TERMINE', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_PAYOUT, 'Litige non fondé');

    expect(
        $booking->fresh()->statusHistories()
            ->where('new_status', BookingStatusEnum::TERMINE)
            ->exists()
    )->toBeTrue();
});

// ── invariants ────────────────────────────────────────────────────────────────

it('refuse si acteur non admin', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->expediteur, ResolveDispute::DECISION_REFUND, 'raison');
})->throws(ValidationException::class);

it('refuse si booking pas EN_LITIGE', function (): void {
    $booking = Booking::factory()->for($this->expediteur)->for($this->trip)->create([
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, 'raison');
})->throws(ValidationException::class);

it('refuse si raison vide', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, '');
})->throws(ValidationException::class);

it('refuse si decision invalide', function (): void {
    ['booking' => $booking] = disputedBookingWithPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, 'compensation', 'raison');
})->throws(ValidationException::class);

it('refuse payout si pas de PAYOUT PENDING — booking venant de CONFIRMEE', function (): void {
    $booking = disputedBookingWithoutPayout($this->expediteur, $this->trip);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_PAYOUT, 'raison');
})->throws(ValidationException::class);

it('est idempotent — refuse si déjà résolu', function (): void {
    $booking = Booking::factory()->for($this->expediteur)->for($this->trip)->create([
        'status'      => BookingStatusEnum::REMBOURSEE,
        'disputed_at' => now()->subHour(),
    ]);

    $this->action->execute($booking, $this->admin, ResolveDispute::DECISION_REFUND, 'raison');
})->throws(ValidationException::class);

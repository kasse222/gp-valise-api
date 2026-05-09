<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Booking;
use App\Models\Trip;
use App\Services\LedgerReader;
use App\Services\LedgerWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);
    $this->reader = app(LedgerReader::class);
    $this->writer = app(LedgerWriter::class);

    // Fixtures communes
    $this->user    = User::factory()->create();
    $this->traveler = User::factory()->traveler()->create();
    $this->trip    = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking = Booking::factory()->for($this->user)->for($this->trip)->create();
});

// ── helpers ──────────────────────────────────────────────────────────────────

function makeCharge(User $user, Booking $booking, int $amount = 10000): Transaction
{
    return Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => $amount,
        'currency'   => CurrencyEnum::EUR->value,
        'processed_at' => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);
}

function makePayout(User $user, Booking $booking, int $amount = 9000): Transaction
{
    return Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::PENDING,
        'amount'     => $amount,
        'currency'   => CurrencyEnum::EUR->value,
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);
}

function makeFee(User $user, Booking $booking, int $amount = 1000): Transaction
{
    return Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::FEE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => $amount,
        'currency'   => CurrencyEnum::EUR->value,
        'processed_at' => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);
}

function makePaymentFee(User $user, Booking $booking, int $amount = 200): Transaction
{
    return Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYMENT_FEE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => $amount,
        'currency'   => CurrencyEnum::EUR->value,
        'processed_at' => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);
}

// ── balanceFor ────────────────────────────────────────────────────────────────

it('retourne 0 si aucune entrée pour ce compte', function (): void {
    expect($this->reader->balanceFor('escrow_eur'))->toBe(0);
});

it('retourne la balance correcte après writeCharge', function (): void {
    $charge = makeCharge($this->user, $this->booking, 10000);
    $this->writer->writeCharge($charge);

    // external_psp_clearing_eur : DEBIT 10000 → balance = -10000
    // escrow_eur                : CREDIT 10000 → balance = +10000
    expect($this->reader->escrowBalance('eur'))->toBe(10000)
        ->and($this->reader->balanceFor('external_psp_clearing_eur'))->toBe(-10000);
});

it('retourne la balance correcte après writePayoutRelease', function (): void {
    $charge = makeCharge($this->user, $this->booking, 10000);
    $payout = makePayout($this->traveler, $this->booking, 9000);
    $fee    = makeFee($this->traveler, $this->booking, 1000);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);

    // escrow_eur : CREDIT 10000 - DEBIT 10000 = 0
    // payable_voyageur_eur : CREDIT 9000
    // revenue_fees_eur : CREDIT 1000
    expect($this->reader->escrowBalance('eur'))->toBe(0)
        ->and($this->reader->payableVoyageurBalance('eur'))->toBe(9000)
        ->and($this->reader->revenueFeesBalance('eur'))->toBe(1000);
});

it('retourne la balance correcte après writePaymentFee', function (): void {
    $charge     = makeCharge($this->user, $this->booking, 10000);
    $paymentFee = makePaymentFee($this->user, $this->booking, 200);

    $this->writer->writePaymentFee($paymentFee);

    // expense_psp_eur : DEBIT 200 → balance = -200
    // external_psp_clearing_eur : CREDIT 200 → balance = +200
    expect($this->reader->expensePspBalance('eur'))->toBe(-200)
        ->and($this->reader->balanceFor('external_psp_clearing_eur'))->toBe(200);
});

it('retourne la balance correcte après writeRefund', function (): void {
    $charge = makeCharge($this->user, $this->booking, 10000);
    $refund = Transaction::factory()->create([
        'user_id'      => $this->user->id,
        'booking_id'   => $this->booking->id,
        'type'         => TransactionTypeEnum::REFUND,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => 10000,
        'currency'     => CurrencyEnum::EUR->value,
        'processed_at' => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);

    $this->writer->writeCharge($charge);
    $this->writer->writeRefund($charge, $refund);

    // escrow_eur : CREDIT 10000 - DEBIT 10000 = 0
    // external_psp_clearing_eur : DEBIT 10000 - CREDIT 10000 = 0
    expect($this->reader->escrowBalance('eur'))->toBe(0)
        ->and($this->reader->balanceFor('external_psp_clearing_eur'))->toBe(0);
});

// ── isBalanced ────────────────────────────────────────────────────────────────

it('isBalanced retourne true si aucune entrée', function (): void {
    expect($this->reader->isBalanced())->toBeTrue();
});

it('isBalanced retourne true après writeCharge', function (): void {
    $charge = makeCharge($this->user, $this->booking, 10000);
    $this->writer->writeCharge($charge);

    expect($this->reader->isBalanced())->toBeTrue();
});

it('isBalanced retourne true après flow complet charge + payout release + payment fee', function (): void {
    $charge     = makeCharge($this->user, $this->booking, 10000);
    $payout     = makePayout($this->traveler, $this->booking, 9000);
    $fee        = makeFee($this->traveler, $this->booking, 1000);
    $paymentFee = makePaymentFee($this->user, $this->booking, 200);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);
    $this->writer->writePaymentFee($paymentFee);

    expect($this->reader->isBalanced())->toBeTrue();
});

it('isBalanced retourne true après writeRefund', function (): void {
    $charge = makeCharge($this->user, $this->booking, 10000);
    $refund = Transaction::factory()->create([
        'user_id'      => $this->user->id,
        'booking_id'   => $this->booking->id,
        'type'         => TransactionTypeEnum::REFUND,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => 10000,
        'currency'     => CurrencyEnum::EUR->value,
        'processed_at' => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);

    $this->writer->writeCharge($charge);
    $this->writer->writeRefund($charge, $refund);

    expect($this->reader->isBalanced())->toBeTrue();
});

it('isBalanced retourne false si entrée manuelle déséquilibrée', function (): void {
    $account = LedgerAccount::where('slug', 'escrow_eur')->firstOrFail();

    // Écriture orpheline — DEBIT sans CREDIT correspondant
    LedgerEntry::create([
        'account_id'     => $account->id,
        'transaction_id' => null,
        'direction'      => 'DEBIT',
        'amount'         => 5000,
        'currency'       => 'EUR',
        'description'    => 'Entrée manuelle déséquilibrée',
    ]);

    expect($this->reader->isBalanced())->toBeFalse();
});

// ── multi-currency ────────────────────────────────────────────────────────────

it('les balances EUR et XOF sont indépendantes', function (): void {
    $chargeEur = makeCharge($this->user, $this->booking, 10000);
    $this->writer->writeCharge($chargeEur);

    // XOF non touché
    expect($this->reader->escrowBalance('eur'))->toBe(10000)
        ->and($this->reader->escrowBalance('xof'))->toBe(0);
});

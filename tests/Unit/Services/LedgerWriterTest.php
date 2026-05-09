<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Services\LedgerReader;
use App\Services\LedgerWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);
    $this->writer = app(LedgerWriter::class);
    $this->reader = app(LedgerReader::class);

    $this->user     = User::factory()->create();
    $this->traveler = User::factory()->traveler()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->for($this->user)->for($this->trip)->create();
});

// ── helpers ───────────────────────────────────────────────────────────────────

function makeTransaction(
    User $user,
    Booking $booking,
    TransactionTypeEnum $type,
    TransactionStatusEnum $status,
    int $amount,
    string $currency = 'EUR',
): Transaction {
    return Transaction::factory()->create([
        'user_id'                 => $user->id,
        'booking_id'              => $booking->id,
        'type'                    => $type,
        'status'                  => $status,
        'amount'                  => $amount,
        'currency'                => $currency,
        'processed_at'            => now(),
        'provider_transaction_id' => 'fake_' . \Illuminate\Support\Str::uuid(),
    ]);
}

function assertEntries(string $slug, int $expectedDebits, int $expectedCredits): void
{
    $account = \App\Models\LedgerAccount::where('slug', $slug)->firstOrFail();

    $debits  = LedgerEntry::where('account_id', $account->id)->where('direction', 'DEBIT')->sum('amount');
    $credits = LedgerEntry::where('account_id', $account->id)->where('direction', 'CREDIT')->sum('amount');

    expect((int) $debits)->toBe($expectedDebits)
        ->and((int) $credits)->toBe($expectedCredits);
}

// ── writeCharge ───────────────────────────────────────────────────────────────

it('writeCharge crée deux entries symétriques', function (): void {
    $charge = makeTransaction(
        $this->user,
        $this->booking,
        TransactionTypeEnum::CHARGE,
        TransactionStatusEnum::COMPLETED,
        10000
    );

    $this->writer->writeCharge($charge);

    expect(LedgerEntry::count())->toBe(2);
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writeCharge débite external_psp_clearing et crédite escrow', function (): void {
    $charge = makeTransaction(
        $this->user,
        $this->booking,
        TransactionTypeEnum::CHARGE,
        TransactionStatusEnum::COMPLETED,
        10000
    );

    $this->writer->writeCharge($charge);

    assertEntries('external_psp_clearing_eur', 10000, 0);
    assertEntries('escrow_eur', 0, 10000);
});

it('writeCharge fonctionne avec XOF', function (): void {
    $charge = makeTransaction(
        $this->user,
        $this->booking,
        TransactionTypeEnum::CHARGE,
        TransactionStatusEnum::COMPLETED,
        50000,
        'XOF'
    );

    $this->writer->writeCharge($charge);

    assertEntries('external_psp_clearing_xof', 50000, 0);
    assertEntries('escrow_xof', 0, 50000);
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writeCharge lie les entries à la transaction', function (): void {
    $charge = makeTransaction(
        $this->user,
        $this->booking,
        TransactionTypeEnum::CHARGE,
        TransactionStatusEnum::COMPLETED,
        10000
    );

    $this->writer->writeCharge($charge);

    expect(LedgerEntry::where('transaction_id', $charge->id)->count())->toBe(2);
});

// ── writePayoutRelease ────────────────────────────────────────────────────────

it('writePayoutRelease crée trois entries symétriques', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING, 9000);
    $fee    = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::FEE, TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writePayoutRelease($charge, $payout, $fee);

    expect(LedgerEntry::count())->toBe(3);
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writePayoutRelease débite escrow et crédite payable_voyageur + revenue_fees', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING, 9000);
    $fee    = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::FEE, TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writePayoutRelease($charge, $payout, $fee);

    assertEntries('escrow_eur', 10000, 0);
    assertEntries('payable_voyageur_eur', 0, 9000);
    assertEntries('revenue_fees_eur', 0, 1000);
});

it('writePayoutRelease lève une exception si montants déséquilibrés', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING, 8000); // ← 8000 ≠ 10000
    $fee    = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::FEE, TransactionStatusEnum::COMPLETED, 1000);

    expect(fn() => $this->writer->writePayoutRelease($charge, $payout, $fee))
        ->toThrow(\RuntimeException::class);

    // aucune entry créée — transaction DB rollback
    expect(LedgerEntry::count())->toBe(0);
});

// ── writePayoutPaid ───────────────────────────────────────────────────────────

it('writePayoutPaid crée deux entries symétriques', function (): void {
    $payout = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writePayoutPaid($payout);

    expect(LedgerEntry::count())->toBe(2);
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writePayoutPaid débite payable_voyageur et crédite external_psp_clearing', function (): void {
    $payout = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writePayoutPaid($payout);

    assertEntries('payable_voyageur_eur', 9000, 0);
    assertEntries('external_psp_clearing_eur', 0, 9000);
});

// ── writePaymentFee ───────────────────────────────────────────────────────────

it('writePaymentFee crée deux entries symétriques', function (): void {
    $paymentFee = makeTransaction($this->user, $this->booking, TransactionTypeEnum::PAYMENT_FEE, TransactionStatusEnum::COMPLETED, 200);

    $this->writer->writePaymentFee($paymentFee);

    expect(LedgerEntry::count())->toBe(2);
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writePaymentFee débite expense_psp et crédite external_psp_clearing', function (): void {
    $paymentFee = makeTransaction($this->user, $this->booking, TransactionTypeEnum::PAYMENT_FEE, TransactionStatusEnum::COMPLETED, 200);

    $this->writer->writePaymentFee($paymentFee);

    assertEntries('expense_psp_eur', 200, 0);
    assertEntries('external_psp_clearing_eur', 0, 200);
});

// ── writeRefund ───────────────────────────────────────────────────────────────

it('writeRefund crée deux entries symétriques', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $refund = makeTransaction($this->user, $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 10000);

    $this->writer->writeCharge($charge);
    $this->writer->writeRefund($charge, $refund);

    expect(LedgerEntry::count())->toBe(4); // 2 charge + 2 refund
    expect($this->reader->isBalanced())->toBeTrue();
});

it('writeRefund débite escrow et crédite external_psp_clearing', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $refund = makeTransaction($this->user, $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 10000);

    $this->writer->writeCharge($charge);
    $this->writer->writeRefund($charge, $refund);

    // escrow : CREDIT 10000 (charge) - DEBIT 10000 (refund) = net 0
    assertEntries('escrow_eur', 10000, 10000);
    // external_psp_clearing : DEBIT 10000 (charge) - CREDIT 10000 (refund) = net 0
    assertEntries('external_psp_clearing_eur', 10000, 10000);
});

// ── lève RuntimeException si compte introuvable ───────────────────────────────

it('writeCharge lève RuntimeException si compte introuvable', function (): void {
    // Désactiver le compte
    \App\Models\LedgerAccount::where('slug', 'escrow_eur')->update(['is_active' => false]);

    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);

    expect(fn() => $this->writer->writeCharge($charge))
        ->toThrow(\RuntimeException::class, 'LedgerAccount introuvable : escrow_eur');

    expect(LedgerEntry::count())->toBe(0);
});

// ── flow complet end-to-end ───────────────────────────────────────────────────

it('flow complet charge + payout release + payment fee est équilibré', function (): void {
    $charge     = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout     = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING, 9000);
    $fee        = makeTransaction($this->traveler, $this->booking, TransactionTypeEnum::FEE, TransactionStatusEnum::COMPLETED, 1000);
    $paymentFee = makeTransaction($this->user, $this->booking, TransactionTypeEnum::PAYMENT_FEE, TransactionStatusEnum::COMPLETED, 200);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);
    $this->writer->writePaymentFee($paymentFee);

    expect(LedgerEntry::count())->toBe(7); // 2 + 3 + 2
    expect($this->reader->isBalanced())->toBeTrue();
});

it('flow complet charge + refund est équilibré', function (): void {
    $charge = makeTransaction($this->user, $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $refund = makeTransaction($this->user, $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 10000);

    $this->writer->writeCharge($charge);
    $this->writer->writeRefund($charge, $refund);

    expect(LedgerEntry::count())->toBe(4);
    expect($this->reader->isBalanced())->toBeTrue();
    expect($this->reader->escrowBalance('eur'))->toBe(0);
});

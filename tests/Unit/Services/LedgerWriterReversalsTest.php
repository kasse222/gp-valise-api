<?php

declare(strict_types=1);

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

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

beforeEach(function (): void {
    $this->seed(\Database\Seeders\LedgerAccountSeeder::class);
    $this->writer   = app(LedgerWriter::class);
    $this->reader   = app(LedgerReader::class);
    $this->user     = User::factory()->create();
    $this->traveler = User::factory()->traveler()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->for($this->user)->for($this->trip)->create();
});

function makeRTx(User $user, Booking $booking, TransactionTypeEnum $type, TransactionStatusEnum $status, int $amount, string $currency = 'XOF'): Transaction
{
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

// ─── writeRefundAfterPayoutRelease() ──────────────────────────────────────

it('writeRefundAfterPayoutRelease crée trois entries symétriques', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);
    $refund = makeRTx($this->user,     $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee);

    // 2 entries sur refund (payable_voyageur + external_psp_clearing)
    // 1 entry sur fee (revenue_fees)
    expect(LedgerEntry::where('transaction_id', $refund->id)->count())->toBe(2)
        ->and(LedgerEntry::where('transaction_id', $fee->id)->count())->toBe(1);
});

it('writeRefundAfterPayoutRelease débite payable_voyageur + revenue_fees, crédite external_psp_clearing', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);
    $refund = makeRTx($this->user,     $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 9000);

    // Simuler état après payout release
    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);

    $payableBefore = $this->reader->payableVoyageurBalance('xof');
    $revenueBefore = $this->reader->revenueFeesBalance('xof');
    $escrowBefore  = $this->reader->escrowBalance('xof');

    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee);

    expect($this->reader->payableVoyageurBalance('xof'))->toBe($payableBefore - 9000)
        ->and($this->reader->revenueFeesBalance('xof'))->toBe($revenueBefore - 1000)
        ->and($this->reader->escrowBalance('xof'))->toBe($escrowBefore); // escrow inchangé
});

it('writeRefundAfterPayoutRelease est idempotent', function () {
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);
    $refund = makeRTx($this->user,     $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee);
    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee); // doublon

    expect(LedgerEntry::where('transaction_id', $refund->id)->count())->toBe(2);
});

it('writeRefundAfterPayoutRelease — ledger reste équilibré (F-017)', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);
    $refund = makeRTx($this->user,     $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);
    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee);

    expect($this->reader->isBalanced())->toBeTrue();
});

// ─── writePayoutReversal() ────────────────────────────────────────────────

it('writePayoutReversal crée trois entries symétriques', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writePayoutReversal($payout, $fee, $charge);

    expect(LedgerEntry::where('transaction_id', $payout->id)->count())->toBe(1)  // DEBIT payable
        ->and(LedgerEntry::where('transaction_id', $charge->id)->count())->toBe(1) // CREDIT escrow
        ->and(LedgerEntry::where('transaction_id', $fee->id)->count())->toBe(1);   // DEBIT revenue_fees
});

it('writePayoutReversal débite payable_voyageur + revenue_fees, recrédite escrow', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);

    $escrowBefore  = $this->reader->escrowBalance('xof');
    $payableBefore = $this->reader->payableVoyageurBalance('xof');
    $revenueBefore = $this->reader->revenueFeesBalance('xof');

    $this->writer->writePayoutReversal($payout, $fee, $charge);

    expect($this->reader->payableVoyageurBalance('xof'))->toBe($payableBefore - 9000)
        ->and($this->reader->revenueFeesBalance('xof'))->toBe($revenueBefore - 1000)
        ->and($this->reader->escrowBalance('xof'))->toBe($escrowBefore + 10000); // escrow recrédité
});

it('writePayoutReversal est idempotent', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writePayoutReversal($payout, $fee, $charge);
    $this->writer->writePayoutReversal($payout, $fee, $charge); // doublon

    expect(LedgerEntry::where('transaction_id', $payout->id)->count())->toBe(1);
});

it('writePayoutReversal — ledger reste équilibré (F-017)', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);
    $this->writer->writePayoutReversal($payout, $fee, $charge);

    expect($this->reader->isBalanced())->toBeTrue();
});

it('writePayoutReversal lève une exception si montants déséquilibrés', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   8000); // 8000+1000 ≠ 10000
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);

    expect(fn() => $this->writer->writePayoutReversal($payout, $fee, $charge))
        ->toThrow(RuntimeException::class, 'Ledger déséquilibré [PAYOUT_REVERSAL]');
});

// ─── Flow complet F-017 ───────────────────────────────────────────────────

it('flow complet charge + payout release + reversal + refund post-release est équilibré', function () {
    $charge = makeRTx($this->user,     $this->booking, TransactionTypeEnum::CHARGE, TransactionStatusEnum::COMPLETED, 10000);
    $payout = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::PAYOUT, TransactionStatusEnum::PENDING,   9000);
    $fee    = makeRTx($this->traveler, $this->booking, TransactionTypeEnum::FEE,    TransactionStatusEnum::COMPLETED, 1000);
    $refund = makeRTx($this->user,     $this->booking, TransactionTypeEnum::REFUND, TransactionStatusEnum::COMPLETED, 9000);

    $this->writer->writeCharge($charge);
    $this->writer->writePayoutRelease($charge, $payout, $fee);
    $this->writer->writePayoutReversal($payout, $fee, $charge);   // dispute → payout annulé
    $this->writer->writeRefundAfterPayoutRelease($refund, $payout, $fee); // refund accordé

    expect($this->reader->isBalanced())->toBeTrue();
});

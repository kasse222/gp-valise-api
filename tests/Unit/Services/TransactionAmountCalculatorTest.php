<?php

declare(strict_types=1);

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Services\TransactionAmountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('gpvalise.fee_percentage', 10);
    config()->set('gpvalise.payment_fee_percentage', 2);

    $this->calculator = app(TransactionAmountCalculator::class);
});

function createCompletedChargeForAmountCalculator(int $amount = 10000): Transaction
{
    return Transaction::factory()->create([
        'type'         => TransactionTypeEnum::CHARGE,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => $amount, // ← centimes : 10000 = 100.00€
        'processed_at' => now(),
    ]);
}

it('calcule la fee depuis la charge', function (): void {
    $charge = createCompletedChargeForAmountCalculator(10000);

    expect($this->calculator->calculateFeeAmount($charge))->toBe(1000); // 10%
});

it('calcule les frais de paiement depuis la charge', function (): void {
    $charge = createCompletedChargeForAmountCalculator(10000);

    expect($this->calculator->calculatePaymentFeeAmount($charge))->toBe(200); // 2%
});

it('calcule le payout sans déduire les frais de paiement', function (): void {
    $charge = createCompletedChargeForAmountCalculator(10000);

    expect($this->calculator->calculatePayoutAmount($charge))->toBe(9000); // 10000 - 1000
});

it('calcule le refund MVP sans déduire les frais de paiement', function (): void {
    $charge = createCompletedChargeForAmountCalculator(10000);

    expect($this->calculator->calculateRefundAmount($charge))->toBe(9000);
});

it('calcule le profit net plateforme', function (): void {
    $charge = createCompletedChargeForAmountCalculator(10000);

    expect($this->calculator->calculateNetProfitAmount($charge))->toBe(800); // 1000 - 200
});

it('utilise les taux configurables', function (): void {
    config()->set('gpvalise.fee_percentage', 15);
    config()->set('gpvalise.payment_fee_percentage', 3);

    $charge = createCompletedChargeForAmountCalculator(20000); // 200.00€

    expect($this->calculator->calculateFeeAmount($charge))->toBe(3000)        // 15%
        ->and($this->calculator->calculatePaymentFeeAmount($charge))->toBe(600) // 3%
        ->and($this->calculator->calculatePayoutAmount($charge))->toBe(17000)   // 20000 - 3000
        ->and($this->calculator->calculateRefundAmount($charge))->toBe(17000)
        ->and($this->calculator->calculateNetProfitAmount($charge))->toBe(2400); // 3000 - 600
});

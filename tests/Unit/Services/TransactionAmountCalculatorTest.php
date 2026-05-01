<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Services\TransactionAmountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('gpvalise.fee_percentage', 10);
    config()->set('gpvalise.payment_fee_percentage', 2);

    $this->calculator = app(TransactionAmountCalculator::class);
});

function createCompletedChargeForAmountCalculator(float $amount = 100): Transaction
{
    return Transaction::factory()->create([
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => $amount,
        'processed_at' => now(),
    ]);
}

it('calcule la fee depuis la charge', function () {
    $charge = createCompletedChargeForAmountCalculator(100);

    expect($this->calculator->calculateFeeAmount($charge))->toBe(10.0);
});

it('calcule les frais de paiement depuis la charge', function () {
    $charge = createCompletedChargeForAmountCalculator(100);

    expect($this->calculator->calculatePaymentFeeAmount($charge))->toBe(2.0);
});

it('calcule le payout sans déduire les frais de paiement', function () {
    $charge = createCompletedChargeForAmountCalculator(100);

    expect($this->calculator->calculatePayoutAmount($charge))->toBe(90.0);
});

it('calcule le refund MVP sans déduire les frais de paiement', function () {
    $charge = createCompletedChargeForAmountCalculator(100);

    expect($this->calculator->calculateRefundAmount($charge))->toBe(90.0);
});

it('calcule le profit net plateforme', function () {
    $charge = createCompletedChargeForAmountCalculator(100);

    expect($this->calculator->calculateNetProfitAmount($charge))->toBe(8.0);
});

it('utilise les taux configurables', function () {
    config()->set('gpvalise.fee_percentage', 15);
    config()->set('gpvalise.payment_fee_percentage', 3);

    $charge = createCompletedChargeForAmountCalculator(200);

    expect($this->calculator->calculateFeeAmount($charge))->toBe(30.0)
        ->and($this->calculator->calculatePaymentFeeAmount($charge))->toBe(6.0)
        ->and($this->calculator->calculatePayoutAmount($charge))->toBe(170.0)
        ->and($this->calculator->calculateRefundAmount($charge))->toBe(170.0)
        ->and($this->calculator->calculateNetProfitAmount($charge))->toBe(24.0);
});

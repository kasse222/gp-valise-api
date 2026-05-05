<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\RefundRequestData;
use App\Enums\PaymentProviderEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\TransactionAmountCalculator;
use App\Services\TransactionEligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RefundTransaction
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
        private readonly TransactionEligibilityService $eligibility,
        private readonly TransactionAmountCalculator $calculator,
    ) {}

    public function execute(Transaction $charge, ?string $reason = null): Transaction
    {
        $result = DB::transaction(function () use ($charge, $reason) {
            $charge = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($charge->id);

            if (! $charge->isCharge() || ! $charge->isSucceeded()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Seule une charge complétée peut être remboursée.',
                ]);
            }

            $booking = Booking::query()
                ->lockForUpdate()
                ->find($charge->booking_id);

            if (! $booking) {
                throw ValidationException::withMessages([
                    'booking' => 'Transaction sans booking.',
                ]);
            }

            Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if (! $this->eligibility->canCreateRefund($booking)) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $refundAmount = $this->calculator->calculateRefundAmount($charge);

            if ($refundAmount <= 0) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucun montant remboursable disponible pour ce booking.',
                ]);
            }

            $providerResult = $this->paymentProvider->refund(
                new RefundRequestData(
                    provider: PaymentProviderEnum::FAKE,
                    providerTransactionId: (string) $charge->provider_transaction_id,
                    amount: (int) round((float) $refundAmount * 100),
                    currency: $charge->currency,
                    idempotencyKey: 'refund-' . $charge->id,
                    reason: $reason ?? 'Refund requested',
                    metadata: [
                        'booking_id' => $booking->id,
                        'user_id' => $charge->user_id,
                    ],
                )
            );

            $status = match ($providerResult->providerStatus) {
                'completed' => TransactionStatusEnum::COMPLETED,
                'pending' => TransactionStatusEnum::PENDING,
                'failed' => TransactionStatusEnum::FAILED,
                default => throw new InvalidArgumentException("Statut provider inconnu : {$providerResult->providerStatus}"),
            };

            if ($status === TransactionStatusEnum::FAILED) {
                throw ValidationException::withMessages([
                    'refund' => 'Le provider de paiement a refusé le remboursement.',
                ]);
            }

            return Transaction::query()->create([
                'user_id' => $charge->user_id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::REFUND,
                'amount' => $refundAmount,
                'currency' => $charge->currency,
                'method' => $charge->method,
                'status' => $status,
                'provider_transaction_id' => $providerResult->providerTransactionId,
                'processed_at' => $status === TransactionStatusEnum::COMPLETED ? now() : null,
            ]);
        });

        event(new TransactionRefunded($result, $reason));

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Enums\PaymentProviderEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\LedgerWriter;
use App\Services\TransactionAmountCalculator;
use App\Services\TransactionEligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RefundTransaction
{
    public function __construct(
        protected readonly PaymentProviderResolverContract $resolver,
        protected readonly TransactionEligibilityService $eligibility,
        protected readonly TransactionAmountCalculator $calculator,
        protected readonly LedgerWriter $ledger,
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

            $providerResult = $this->callProviderRefund(
                charge: $charge,
                refundAmount: $refundAmount,
                idempotencyKey: 'refund-' . $charge->id,
                reason: $reason ?? 'Refund requested',
                metadata: [
                    'booking_id' => $booking->id,
                    'user_id'    => $charge->user_id,
                ],
            );

            $status = $this->resolveStatus($providerResult->providerStatus);

            if ($status === TransactionStatusEnum::FAILED) {
                throw ValidationException::withMessages([
                    'refund' => 'Le provider de paiement a refusé le remboursement.',
                ]);
            }

            $refund = Transaction::query()->create([
                'user_id'                 => $charge->user_id,
                'booking_id'              => $booking->id,
                'type'                    => TransactionTypeEnum::REFUND,
                'amount'                  => $refundAmount,
                'currency'                => $charge->currency,
                'method'                  => $charge->method,
                'status'                  => $status,
                'provider'                => $charge->provider,
                'provider_transaction_id' => $providerResult->providerTransactionId,
                'processed_at'            => $status === TransactionStatusEnum::COMPLETED ? now() : null,
            ]);

            // F-021 — écriture ledger uniquement si refund COMPLETED
            // PENDING = remboursement manuel en attente → ledger écrit par le webhook refund.completed
            if ($status === TransactionStatusEnum::COMPLETED) {
                $this->ledger->writeRefund($charge, $refund);
            }

            return $refund;
        });

        event(new TransactionRefunded($result, $reason));

        return $result;
    }

    // ─── Shared helpers ────────────────────────────────────────────────────────

    protected function callProviderRefund(
        Transaction $charge,
        int $refundAmount,
        string $idempotencyKey,
        string $reason,
        array $metadata,
    ): PaymentResponseData {
        $providerKey = $charge->provider?->value ?? PaymentProviderEnum::PAYDUNYA->value;

        $refundRequest = new RefundRequestData(
            provider: $charge->provider ?? PaymentProviderEnum::PAYDUNYA,
            providerTransactionId: (string) $charge->provider_transaction_id,
            amount: $refundAmount,
            currency: $charge->currency,
            idempotencyKey: $idempotencyKey,
            reason: $reason,
            metadata: $metadata,
        );

        try {
            $provider = $this->resolver->resolveByKey($providerKey);
            return $provider->refund($refundRequest);
        } catch (\RuntimeException $e) {
            // Provider ne supporte pas le refund automatique (ex: PayDunya)
            // → PENDING pour traitement manuel admin
            Log::warning('Refund provider non supporté — traitement manuel requis', [
                'provider'   => $providerKey,
                'charge_id'  => $charge->id,
                'booking_id' => $charge->booking_id,
                'error'      => $e->getMessage(),
            ]);

            return new PaymentResponseData(
                provider: $charge->provider ?? PaymentProviderEnum::PAYDUNYA,
                providerTransactionId: 'manual-refund-' . $charge->id,
                providerStatus: 'pending',
                amount: $refundAmount,
                currency: $charge->currency,
                checkoutUrl: null,
                eventId: null,
                rawPayload: ['manual' => true, 'reason' => $e->getMessage()],
            );
        }
    }

    protected function resolveStatus(string $providerStatus): TransactionStatusEnum
    {
        return match ($providerStatus) {
            'completed' => TransactionStatusEnum::COMPLETED,
            'pending'   => TransactionStatusEnum::PENDING,
            'failed'    => TransactionStatusEnum::FAILED,
            default     => throw new InvalidArgumentException("Statut provider inconnu : {$providerStatus}"),
        };
    }
}

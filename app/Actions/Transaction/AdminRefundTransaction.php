<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentProviderEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditLogIntegrityService;
use App\Services\TransactionAmountCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AdminRefundTransaction
{
    public function __construct(
        private readonly PaymentProviderResolverContract $resolver,
        private readonly TransactionAmountCalculator $calculator,
        private readonly AuditLogIntegrityService $auditLogIntegrityService,
    ) {}

    public function execute(User $admin, Transaction $charge, string $reason, ?string $correlationId = null): Transaction
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'admin' => 'Seul un administrateur peut forcer un remboursement.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'La raison du remboursement forcé est obligatoire.',
            ]);
        }

        $refund = DB::transaction(function () use ($admin, $charge, $reason, $correlationId) {
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

            if ($booking->status !== BookingStatusEnum::EN_LITIGE) {
                throw ValidationException::withMessages([
                    'booking' => 'Un remboursement admin forcé nécessite un booking en litige.',
                ]);
            }

            $transactions = Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if ($transactions->contains(
                fn(Transaction $t) =>
                $t->type === TransactionTypeEnum::PAYOUT
                    && in_array($t->status, [
                        TransactionStatusEnum::PENDING,
                        TransactionStatusEnum::COMPLETED,
                    ], true)
            )) {
                throw ValidationException::withMessages([
                    'booking' => 'Impossible de rembourser après payout.',
                ]);
            }

            $existingRefund = $transactions->first(
                fn(Transaction $t) => $t->type === TransactionTypeEnum::REFUND
            );

            if ($existingRefund) {
                return $existingRefund;
            }

            $refundAmount = $this->calculator->calculateRefundAmount($charge);

            if ($refundAmount <= 0) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucun montant remboursable disponible.',
                ]);
            }

            $providerResult = $this->callProviderRefund(
                charge: $charge,
                refundAmount: $refundAmount,
                idempotencyKey: 'admin-refund-' . $charge->id,
                reason: $reason,
                metadata: [
                    'booking_id'     => $booking->id,
                    'user_id'        => $charge->user_id,
                    'admin_id'       => $admin->id,
                    'correlation_id' => $correlationId,
                ],
            );

            $status = $this->resolveStatus($providerResult->providerStatus);

            if ($status === TransactionStatusEnum::FAILED) {
                throw ValidationException::withMessages([
                    'refund' => 'Le provider a refusé le remboursement.',
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

            $snapshot = [
                'reason'  => $reason,
                'admin'   => ['id' => $admin->id, 'email' => $admin->email],
                'booking' => ['id' => $booking->id, 'status' => $booking->status->value],
                'charge'  => [
                    'id'       => $charge->id,
                    'amount'   => (float) $charge->amount,
                    'currency' => $charge->currency?->value,
                    'status'   => $charge->status?->value,
                    'provider' => $charge->provider?->value,
                ],
                'transactions' => $transactions->map(fn(Transaction $t) => [
                    'id'     => $t->id,
                    'type'   => $t->type?->value,
                    'status' => $t->status?->value,
                    'amount' => (float) $t->amount,
                ])->values()->all(),
                'refund'     => ['id' => $refund->id, 'amount' => (float) $refund->amount, 'status' => $refund->status?->value],
                'created_at' => now()->toISOString(),
            ];

            $snapshot['hash'] = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));

            $auditLog = AuditLog::query()->create([
                'actor_id'       => $admin->id,
                'action'         => 'admin_refund_override',
                'auditable_type' => Transaction::class,
                'auditable_id'   => $refund->id,
                'metadata'       => $snapshot,
                'correlation_id' => $correlationId,
            ]);

            $this->auditLogIntegrityService->seal($auditLog);

            return $refund;
        });

        event(new TransactionRefunded($refund, $reason));

        return $refund;
    }

    private function callProviderRefund(
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
            return $this->resolver->resolveByKey($providerKey)->refund($refundRequest);
        } catch (\RuntimeException $e) {
            Log::warning('AdminRefund provider non supporté — traitement manuel requis', [
                'provider'   => $providerKey,
                'charge_id'  => $charge->id,
                'booking_id' => $charge->booking_id,
                'error'      => $e->getMessage(),
            ]);

            return new PaymentResponseData(
                provider: $charge->provider ?? PaymentProviderEnum::PAYDUNYA,
                providerTransactionId: 'manual-refund-' . $charge->id,
                providerStatus: 'pending_manual',
                amount: $refundAmount,
                currency: $charge->currency,
                checkoutUrl: null,
                eventId: null,
                rawPayload: ['manual' => true, 'reason' => $e->getMessage()],
            );
        }
    }

    private function resolveStatus(string $providerStatus): TransactionStatusEnum
    {
        $status = match ($providerStatus) {
            'completed'      => TransactionStatusEnum::COMPLETED,
            'pending',
            'pending_manual' => TransactionStatusEnum::PENDING,
            'failed'         => TransactionStatusEnum::FAILED,
            default          => null,
        };

        if ($status === null) {
            Log::warning('AdminRefund resolveStatus : statut provider inconnu — PENDING par défaut', [
                'provider_status' => $providerStatus,
            ]);
            return TransactionStatusEnum::PENDING;
        }

        return $status;
    }
}

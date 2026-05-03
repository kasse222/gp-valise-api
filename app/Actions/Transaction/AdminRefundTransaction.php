<?php

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProvider;
use App\Enums\BookingStatusEnum;
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
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AdminRefundTransaction
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
        private readonly TransactionAmountCalculator $calculator,
        private readonly AuditLogIntegrityService $auditLogIntegrityService,
    ) {}

    public function execute(User $admin, Transaction $charge, string $reason): Transaction
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

        $refund = DB::transaction(function () use ($admin, $charge, $reason) {
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

            if ($transactions->contains(fn(Transaction $transaction) => $transaction->type === TransactionTypeEnum::PAYOUT)) {
                throw ValidationException::withMessages([
                    'booking' => 'Impossible de rembourser après payout.',
                ]);
            }

            $existingRefund = $transactions->first(
                fn(Transaction $transaction) => $transaction->type === TransactionTypeEnum::REFUND
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

            $providerResult = $this->paymentProvider->refund([
                'booking_id' => $booking->id,
                'user_id' => $charge->user_id,
                'amount' => $refundAmount,
                'currency' => $charge->currency?->value,
                'method' => $charge->method?->value,
                'reason' => $reason,
            ]);

            if (! $providerResult->success) {
                throw ValidationException::withMessages([
                    'refund' => $providerResult->message ?? 'Le provider a refusé le remboursement.',
                ]);
            }

            $status = match ($providerResult->status) {
                'completed' => TransactionStatusEnum::COMPLETED,
                'pending' => TransactionStatusEnum::PENDING,
                'failed' => TransactionStatusEnum::FAILED,
                default => throw new InvalidArgumentException("Statut provider inconnu : {$providerResult->status}"),
            };

            $refund = Transaction::query()->create([
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

            $snapshot = [
                'reason' => $reason,
                'admin' => [
                    'id' => $admin->id,
                    'email' => $admin->email,
                ],
                'booking' => [
                    'id' => $booking->id,
                    'status' => $booking->status->value,
                ],
                'charge' => [
                    'id' => $charge->id,
                    'amount' => (float) $charge->amount,
                    'currency' => $charge->currency?->value,
                    'status' => $charge->status?->value,
                ],
                'transactions' => $transactions->map(fn(Transaction $transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type?->value,
                    'status' => $transaction->status?->value,
                    'amount' => (float) $transaction->amount,
                ])->values()->all(),
                'refund' => [
                    'id' => $refund->id,
                    'amount' => (float) $refund->amount,
                    'status' => $refund->status?->value,
                ],
                'created_at' => now()->toISOString(),
            ];

            $snapshot['hash'] = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));

            $auditLog = AuditLog::query()->create([
                'actor_id' => $admin->id,
                'action' => 'admin_refund_override',
                'auditable_type' => Transaction::class,
                'auditable_id' => $refund->id,
                'metadata' => $snapshot,
            ]);

            $this->auditLogIntegrityService->seal($auditLog);

            return $refund;
        });

        event(new TransactionRefunded($refund, $reason));

        return $refund;
    }
}

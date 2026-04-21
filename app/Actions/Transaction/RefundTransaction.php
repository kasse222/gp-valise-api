<?php

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProvider;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RefundTransaction
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
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

            if (! in_array($booking->status, [
                BookingStatusEnum::CONFIRMEE,
                BookingStatusEnum::LIVREE,
                BookingStatusEnum::EN_LITIGE,
            ], true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $hasPayout = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::PAYOUT)
                ->exists();

            if ($hasPayout) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $hasRefund = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::REFUND)
                ->exists();

            if ($hasRefund) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $completedChargeAmount = (float) Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::CHARGE)
                ->where('status', TransactionStatusEnum::COMPLETED)
                ->sum('amount');

            $completedRefundAmount = (float) Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::REFUND)
                ->sum('amount');

            $refundAmount = round($completedChargeAmount - $completedRefundAmount, 2);

            if ($refundAmount <= 0) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucun montant remboursable disponible pour ce booking.',
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
                    'refund' => $providerResult->message ?? 'Le provider de paiement a refusé le remboursement.',
                ]);
            }

            $status = match ($providerResult->status) {
                'completed' => TransactionStatusEnum::COMPLETED,
                'pending' => TransactionStatusEnum::PENDING,
                'failed' => TransactionStatusEnum::FAILED,
                default => throw new InvalidArgumentException("Statut provider inconnu : {$providerResult->status}"),
            };

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

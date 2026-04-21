<?php

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProvider;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionCreated;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateTransaction
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
    ) {}

    public function execute(User $user, array $data): Transaction
    {
        if (($data['amount'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif.');
        }

        $transaction = DB::transaction(function () use ($user, $data) {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($data['booking_id']);

            if ((int) $booking->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Ce booking ne vous appartient pas.',
                ]);
            }

            if ($booking->status !== BookingStatusEnum::EN_PAIEMENT) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Ce booking n’est pas dans un état permettant un paiement.',
                ]);
            }

            if ($booking->payment_expires_at === null || $booking->payment_expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Le délai de paiement de ce booking a expiré.',
                ]);
            }

            $existingCharge = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::CHARGE)
                ->exists();

            if ($existingCharge) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Une transaction existe déjà pour ce booking.',
                ]);
            }

            $providerResult = $this->paymentProvider->charge([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? null,
                'method' => $data['method'] ?? null,
            ]);

            if (! $providerResult->success) {
                throw ValidationException::withMessages([
                    'payment' => $providerResult->message ?? 'Le provider de paiement a refusé la charge.',
                ]);
            }

            $status = match ($providerResult->status) {
                'completed' => TransactionStatusEnum::COMPLETED,
                'pending' => TransactionStatusEnum::PENDING,
                'failed' => TransactionStatusEnum::FAILED,
                default => throw new InvalidArgumentException("Statut provider inconnu : {$providerResult->status}"),
            };

            return $user->transactions()->create([
                ...$data,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::CHARGE,
                'status' => $status,
                'provider_transaction_id' => $providerResult->providerTransactionId,
                'processed_at' => $status === TransactionStatusEnum::COMPLETED ? now() : null,
            ])->fresh();
        });

        event(new TransactionCreated($transaction));

        return $transaction;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentRequestData;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentOperatorEnum;
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
        private readonly PaymentProviderResolverContract $resolver,
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

            Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            $existingCharge = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::CHARGE)
                ->exists();

            if ($existingCharge) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Une transaction existe déjà pour ce booking.',
                ]);
            }

            $currency = $data['currency'] instanceof CurrencyEnum
                ? $data['currency']
                : CurrencyEnum::from((string) ($data['currency'] ?? CurrencyEnum::EUR->value));

            $method = $data['method'] instanceof PaymentMethodEnum
                ? $data['method']
                : PaymentMethodEnum::from((string) ($data['method'] ?? PaymentMethodEnum::CARD->value));

            $operator = isset($data['operator'])
                ? ($data['operator'] instanceof PaymentOperatorEnum
                    ? $data['operator']
                    : PaymentOperatorEnum::from((string) $data['operator']))
                : null;

            $paymentRequest = new PaymentRequestData(
                country: (string) ($data['country'] ?? 'FR'),
                currency: $currency,
                method: $method,
                amount: (int) round((float) $data['amount'] * 100),
                idempotencyKey: 'charge-' . $booking->id,
                operator: $operator,
                metadata: [
                    'booking_id'         => $booking->id,
                    'user_id'            => $user->id,
                    'customer_phone'     => $data['phone'] ?? null,
                    'customer_email'     => $user->email,
                    'customer_firstname' => $user->name ?? '',
                    'customer_lastname'  => '',
                    'callback_url'       => config('app.url') . '/api/v1/webhooks/paydunya',
                    'correlation_id'     => $data['correlation_id'] ?? null,
                ],
            );

            $providerResult = $this->resolver->resolve($paymentRequest)->charge($paymentRequest);

            $status = match ($providerResult->providerStatus) {
                'completed' => TransactionStatusEnum::COMPLETED,
                'pending' => TransactionStatusEnum::PENDING,
                'failed' => TransactionStatusEnum::FAILED,
                default => throw new InvalidArgumentException("Statut provider inconnu : {$providerResult->providerStatus}"),
            };

            if ($status === TransactionStatusEnum::FAILED) {
                throw ValidationException::withMessages([
                    'payment' => 'Le provider de paiement a refusé la charge.',
                ]);
            }

            return $user->transactions()->create([
                ...$data,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::CHARGE,
                'status' => $status,
                'currency' => $currency,
                'method' => $method,
                'provider_transaction_id' => $providerResult->providerTransactionId,
                'processed_at' => $status === TransactionStatusEnum::COMPLETED ? now() : null,
            ])->fresh();
        });

        event(new TransactionCreated($transaction));

        return $transaction;
    }
}

<?php

namespace App\Actions\Transaction;

use App\Events\TransactionCreated;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateTransaction
{
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

            if ($booking->transaction()->exists()) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Une transaction existe déjà pour ce booking.',
                ]);
            }

            return $user->transactions()->create([
                ...$data,
                'booking_id' => $booking->id,
            ])->fresh();
        });

        event(new TransactionCreated($transaction));

        return $transaction;
    }
}

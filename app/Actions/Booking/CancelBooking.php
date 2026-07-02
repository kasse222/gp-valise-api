<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Transaction\RefundTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\BookingCanceled;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function __construct(
        private readonly RefundTransaction $refundTransaction,
    ) {}

    public function execute(Booking $booking, ?User $actor = null, string $cancelledBy = 'sender'): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor, $cancelledBy) {
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip', 'user', 'transactions'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->status->canTransitionTo(BookingStatusEnum::ANNULE)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être annulée depuis son statut actuel.',
                ]);
            }

            $refundRate = $booking->computeRefundRate($cancelledBy);

            $booking->cancel_reason = match ($cancelledBy) {
                'traveler' => 'Annulation par le voyageur',
                'admin'    => 'Annulation administrative',
                default    => "Annulation par l'expéditeur",
            };
            $booking->refund_rate = $refundRate;
            $booking->save();

            $booking->transitionTo(
                BookingStatusEnum::ANNULE,
                $actor,
                $booking->cancel_reason
            );

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            return $booking->fresh([
                'bookingItems.luggage',
                'transactions',
                'trip',
                'user',
                'statusHistories',
            ]);
        });

        // F-014 — déclencher le remboursement PSP après la transaction DB
        $this->triggerRefundIfEligible($booking, $cancelledBy);

        event(new BookingCanceled($booking));

        return $booking;
    }

    /**
     * F-014 / F-033 — Déclenche un remboursement PSP si :
     * - une CHARGE COMPLETED existe sur ce booking
     * - le taux de remboursement est > 0
     * - aucun REFUND n'existe déjà (idempotence)
     *
     * F-033 : le taux (100/70/0%) est maintenant appliqué au montant réel.
     */
    private function triggerRefundIfEligible(Booking $booking, string $cancelledBy): void
    {
        $refundRate = $booking->refund_rate ?? 0;

        if ($refundRate <= 0) {
            Log::info('CancelBooking: taux remboursement = 0 — pas de refund PSP', [
                'booking_id'   => $booking->id,
                'cancelled_by' => $cancelledBy,
                'refund_rate'  => $refundRate,
            ]);
            return;
        }

        $transactions = $booking->transactions ?? collect();

        $charge = $transactions->first(
            fn($tx) => $tx->type === TransactionTypeEnum::CHARGE
                && $tx->status === TransactionStatusEnum::COMPLETED
        );

        if (! $charge) {
            return;
        }

        $existingRefund = $transactions->first(
            fn($tx) => $tx->type === TransactionTypeEnum::REFUND
        );

        if ($existingRefund) {
            Log::info('CancelBooking: refund déjà existant — ignoré', [
                'booking_id' => $booking->id,
                'refund_id'  => $existingRefund->id,
            ]);
            return;
        }

        try {
            $this->refundTransaction->execute(
                charge: $charge,
                reason: "Annulation booking#{$booking->id} — taux {$refundRate}% ({$cancelledBy})",
                refundRatePercent: $refundRate,   // F-033
            );
        } catch (\Throwable $e) {
            Log::error('CancelBooking: échec remboursement PSP — traitement manuel requis', [
                'booking_id'   => $booking->id,
                'charge_id'    => $charge->id,
                'refund_rate'  => $refundRate,
                'cancelled_by' => $cancelledBy,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}

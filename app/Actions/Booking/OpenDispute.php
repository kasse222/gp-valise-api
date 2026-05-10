<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Events\BookingDisputed;
use App\Events\DisputeStatusChanged;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpenDispute
{
    public function execute(Booking $booking, User $actor, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $this->validate($booking, $actor, $reason);

            // ── Booking ───────────────────────────────────────────────────────
            $booking->update(['disputed_at' => now()]);

            $booking->transitionTo(
                BookingStatusEnum::EN_LITIGE,
                $actor,
                $reason,
            );

            // ── Dispute v2 ────────────────────────────────────────────────────
            $dispute = Dispute::create([
                'booking_id' => $booking->id,
                'status'     => DisputeStatusEnum::OPEN,
                'opened_by'  => $actor->id,
                'reason'     => $reason,
            ]);

            $dispute->statusHistories()->create([
                'old_status' => null,
                'new_status' => DisputeStatusEnum::OPEN->value,
                'changed_by' => $actor->id,
                'reason'     => $reason,
            ]);
            // ─────────────────────────────────────────────────────────────────

            BookingDisputed::dispatch($booking);

            event(new DisputeStatusChanged(
                dispute: $dispute,
                oldStatus: null,
                newStatus: DisputeStatusEnum::OPEN,
                reason: $reason,
            ));

            return $booking->fresh();
        });
    }

    private function validate(Booking $booking, User $actor, string $reason): void
    {
        // Acteur autorisé
        if (
            $booking->user_id !== $actor->id
            && ! in_array($actor->role, [UserRoleEnum::ADMIN, UserRoleEnum::SUPER_ADMIN], true)
        ) {
            throw ValidationException::withMessages([
                'actor' => 'Vous n\'êtes pas autorisé à ouvrir un litige sur ce booking.',
            ]);
        }

        // Statut disputeable
        if (! $booking->canEnterDispute()) {
            throw ValidationException::withMessages([
                'booking' => "Ce booking ne peut pas entrer en litige (statut : {$booking->status->value}).",
            ]);
        }

        // Raison obligatoire
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'La raison du litige est obligatoire.',
            ]);
        }

        // Idempotence — dispute déjà ouverte
        if ($booking->disputed_at !== null) {
            throw ValidationException::withMessages([
                'booking' => 'Un litige est déjà ouvert sur ce booking.',
            ]);
        }

        // Payout COMPLETED bloque la dispute
        if (
            $booking->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible d\'ouvrir un litige après un payout complété.',
            ]);
        }

        // Refund existant bloque la dispute
        if (
            $booking->transactions()
            ->where('type', TransactionTypeEnum::REFUND)
            ->whereIn('status', [
                TransactionStatusEnum::COMPLETED,
                TransactionStatusEnum::PENDING,
            ])
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible d\'ouvrir un litige après un remboursement.',
            ]);
        }
    }
}

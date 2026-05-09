<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Events\BookingDisputed;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpenDispute
{
    public function execute(Booking $booking, User $actor, string $reason): Booking
    {
        $this->validate($booking, $actor, $reason);

        $booking = DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            // Re-valider sous lock
            $this->validate($booking, $actor, $reason);

            $booking->disputed_at = now();
            $booking->save();

            $booking->transitionTo(
                BookingStatusEnum::EN_LITIGE,
                $actor,
                $reason,
            );

            return $booking->fresh();
        });

        event(new BookingDisputed($booking));

        return $booking;
    }

    private function validate(Booking $booking, User $actor, string $reason): void
    {
        // Raison obligatoire
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Une raison est obligatoire pour ouvrir un litige.',
            ]);
        }

        // Actor : expéditeur du booking ou admin
        $isOwner = (int) $booking->user_id === (int) $actor->id;
        $isAdmin = $actor->role === UserRoleEnum::ADMIN
            || $actor->role === UserRoleEnum::SUPER_ADMIN;

        if (! $isOwner && ! $isAdmin) {
            throw ValidationException::withMessages([
                'actor' => 'Seul l\'expéditeur ou un admin peut ouvrir un litige.',
            ]);
        }

        // Statut doit permettre la dispute
        if (! $booking->status->canEnterDispute()) {
            throw ValidationException::withMessages([
                'booking' => "Ce booking ne peut pas être mis en litige depuis le statut {$booking->status->value}.",
            ]);
        }

        // Déjà disputé — idempotence stricte
        if ($booking->hasActiveDispute()) {
            throw ValidationException::withMessages([
                'booking' => 'Ce booking a déjà un litige actif.',
            ]);
        }

        // Payout existant → dispute refusée
        if ($booking->transactions()->where('type', TransactionTypeEnum::PAYOUT)->exists()) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible d\'ouvrir un litige : un payout existe déjà.',
            ]);
        }

        // Refund existant → dispute refusée
        if ($booking->transactions()->where('type', TransactionTypeEnum::REFUND)->exists()) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible d\'ouvrir un litige : un remboursement existe déjà.',
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;

class TransactionEligibilityService
{
    public function canCreatePayout(Booking $booking): bool
    {
        return $booking->status === BookingStatusEnum::LIVREE
            && $booking->isEscrowReleasable()          // ← nouveau check escrow
            && $this->hasCompletedCharge($booking)
            && ! $this->hasPayout($booking)
            && ! $this->hasRefund($booking)
            && ! $this->hasFee($booking);
    }

    public function canCreateRefund(Booking $booking): bool
    {
        return $this->hasRefundableStatus($booking)
            && $this->hasCompletedCharge($booking)
            && ! $this->hasRefund($booking)
            && ! $this->hasPayout($booking);
    }

    public function hasCompletedCharge(Booking $booking): bool
    {
        return $booking->transactions()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->exists();
    }

    public function hasPayout(Booking $booking): bool
    {
        return $booking->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->whereIn('status', [
                TransactionStatusEnum::PENDING,
                TransactionStatusEnum::COMPLETED,
            ])
            ->exists();
    }

    public function hasRefund(Booking $booking): bool
    {
        return $booking->transactions()
            ->where('type', TransactionTypeEnum::REFUND)
            ->exists();
    }

    public function hasFee(Booking $booking): bool
    {
        return $booking->transactions()
            ->where('type', TransactionTypeEnum::FEE)
            ->exists();
    }

    public function completedCharge(Booking $booking): ?Transaction
    {
        return $booking->transactions()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->latest()
            ->first();
    }

    private function hasRefundableStatus(Booking $booking): bool
    {
        return in_array($booking->status, [
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::EN_LITIGE,
            // F-032 — ANNULE ajouté : CancelBooking transitionne AVANT de
            // tenter le refund, donc le statut au moment du check est déjà ANNULE.
            BookingStatusEnum::ANNULE,
        ], true);
    }
}

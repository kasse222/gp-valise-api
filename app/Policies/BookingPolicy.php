<?php

namespace App\Policies;

use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRoleEnum::SENDER;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isAdmin($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->isSender($user, $booking)
            || $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    /**
     * Remise physique sender → traveler au point de RDV.
     * CONFIRMEE → EN_TRANSIT
     * Seul le voyageur du trip peut confirmer la remise.
     */
    public function handover(User $user, Booking $booking): bool
    {
        if (! ($this->isTraveler($user, $booking) || $this->isAdmin($user))) {
            return false;
        }

        return $booking->status === BookingStatusEnum::CONFIRMEE;
    }

    /**
     * Scan QR / saisie code secret par le destinataire.
     * EN_TRANSIT → LIVREE
     * Seul le voyageur du trip peut valider la livraison.
     */
    public function deliver(User $user, Booking $booking): bool
    {
        if (! ($this->isTraveler($user, $booking) || $this->isAdmin($user))) {
            return false;
        }

        return $booking->status === BookingStatusEnum::EN_TRANSIT;
    }

    // ── @deprecated — Instant Booking — conservés pour compat Filament ────────

    /** @deprecated Instant Booking */
    public function approve(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    /** @deprecated Instant Booking */
    public function decline(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    /** @deprecated Instant Booking */
    public function confirm(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    /** @deprecated Instant Booking — remplacé par handover() + deliver() */
    public function complete(User $user, Booking $booking): bool
    {
        return $this->isTraveler($user, $booking)
            || $this->isAdmin($user);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isSender(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    private function isTraveler(User $user, Booking $booking): bool
    {
        return $booking->trip !== null
            && $user->id === $booking->trip->user_id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === UserRoleEnum::ADMIN;
    }
}

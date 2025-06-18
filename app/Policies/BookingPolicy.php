<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Status\BookingStatus;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $booking->canBeUpdatedTo(BookingStatus::from(request('status')), $user);
    }


    /**
     * Autorise la confirmation si :
     * - l'utilisateur est le propriétaire du Trip
     * - la réservation est en attente
     */
    public function confirm(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id
            && $booking->status === BookingStatus::EN_ATTENTE;
    }

    /**
     * Autorise l’annulation si :
     * - le voyageur (propriétaire du trip) annule un booking en attente
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return (
            // Voyageur
            $user->id === $booking->trip->user_id
            ||
            // Expéditeur
            $user->id === $booking->user_id
        ) && in_array($booking->status, [
            BookingStatus::EN_ATTENTE,
            BookingStatus::ACCEPTE
        ]);
    }


    /**
     * Autorise la complétion si :
     * - le voyageur (propriétaire du trip)
     * - statut déjà confirmé
     */
    public function complete(User $user, Booking $booking): bool
    {
        return $user->id === $booking->trip->user_id
            && $booking->status === BookingStatus::CONFIRMEE;
    }
}

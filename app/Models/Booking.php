<?php

namespace App\Models;

use App\Status\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'trip_id',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the trip associated with the booking.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the booking  Ajouter les relations inverses
     */
    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }


    /**
     * Get the payment associated with this booking.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }


    /**
     * Scope for pending bookings.
     */
    public function scopeEnAttente($query)
    {
        return $query->where('status', 'en_attente');
    }

    /**
     * Scope for accepted bookings.
     */
    public function scopeAccepte($query)
    {
        return $query->where('status', 'accepte');
    }

    /**
     * Scope for refused bookings.
     */
    public function scopeRefuse($query)
    {
        return $query->where('status', 'refuse');
    }

    //centralise la règle métier
    public function canBeConfirmed(): bool
    {
        return $this->status === 'en_attente' && $this->trip && $this->trip->user_id === auth()->id();
    }

    protected $casts = [
        'status' => BookingStatus::class,
    ];


    public function canBeUpdatedTo(BookingStatus $newStatus, User $user): bool
    {
        return match ($newStatus) {
            // Voyageur peut accepter/refuser une réservation en attente
            BookingStatus::ACCEPTE, BookingStatus::REFUSE =>
            $this->status === BookingStatus::EN_ATTENTE && $user->id === $this->trip->user_id,

            // Expéditeur peut annuler avant confirmation ou acceptation
            BookingStatus::ANNULE =>
            in_array($this->status, [BookingStatus::EN_ATTENTE, BookingStatus::ACCEPTE]) && $user->id === $this->user_id,

            // Voyageur marque comme terminé si déjà payé
            BookingStatus::TERMINE =>
            $this->status === BookingStatus::ACCEPTE && $user->id === $this->trip->user_id,

            // Transition interne : en_attente → en_paiement
            BookingStatus::EN_PAIEMENT =>
            $this->status === BookingStatus::EN_ATTENTE && $user->id === $this->user_id,

            // Paiement validé → confirmée (automatique, donc système)
            BookingStatus::CONFIRMEE =>
            $this->status === BookingStatus::EN_PAIEMENT, // pas de user check

            // Livraison par le voyageur
            BookingStatus::LIVREE =>
            $this->status === BookingStatus::CONFIRMEE && $user->id === $this->trip->user_id,

            // Litige peut être déclenché si confirmée ou livrée
            BookingStatus::EN_LITIGE =>
            in_array($this->status, [BookingStatus::CONFIRMEE, BookingStatus::LIVREE]),

            // Paiement échoué → déclenché par système
            BookingStatus::PAIEMENT_ECHOUE =>
            $this->status === BookingStatus::EN_PAIEMENT,

            // Remboursement possible uniquement après annulation ou litige
            BookingStatus::REMBOURSEE =>
            in_array($this->status, [BookingStatus::ANNULE, BookingStatus::EN_LITIGE]),

            // Date dépassée → système
            BookingStatus::EXPIREE =>
            in_array($this->status, [BookingStatus::EN_ATTENTE, BookingStatus::EN_PAIEMENT]),

            // Suspendue manuellement (admin ou système)
            BookingStatus::SUSPENDUE =>
            true,

            default => false,
        };
    }

    public function transitionTo(BookingStatus $newStatus, User $user, ?string $reason = null): bool
    {
        if (! $this->canBeUpdatedTo($newStatus, $user)) {
            return false;
        }

        return DB::transaction(function () use ($newStatus, $user, $reason) {
            // Historique
            BookingStatusHistory::log(
                booking: $this,
                old: $this->status,
                new: $newStatus,
                user: $user,
                reason: $reason
            );

            // Mise à jour du statut
            $this->update(['status' => $newStatus]);

            return true;
        });
    }
}

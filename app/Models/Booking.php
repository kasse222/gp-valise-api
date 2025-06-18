<?php

namespace App\Models;

use App\Status\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
    ];

    // 📌 Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    // 📌 Scopes pratiques
    public function scopeEnAttente($query)
    {
        return $query->where('status', BookingStatus::EN_ATTENTE->value);
    }

    public function scopeAccepte($query)
    {
        return $query->where('status', BookingStatus::ACCEPTE->value);
    }

    public function scopeRefuse($query)
    {
        return $query->where('status', BookingStatus::REFUSE->value);
    }

    // 📌 Règle spécifique : confirmation
    public function canBeConfirmed(): bool
    {
        return $this->status === BookingStatus::EN_ATTENTE
            && $this->trip
            && $this->trip->user_id === auth()->id();
    }

    // ✅ Règles métier de transition de statut
    public function canBeUpdatedTo(BookingStatus $newStatus, User $user): bool
    {
        return match ($newStatus) {
            BookingStatus::ACCEPTE, BookingStatus::REFUSE =>
            $this->status === BookingStatus::EN_ATTENTE && $user->id === $this->trip->user_id,

            BookingStatus::ANNULE =>
            in_array($this->status, [BookingStatus::EN_ATTENTE, BookingStatus::ACCEPTE])
                && $user->id === $this->user_id,

            BookingStatus::TERMINE =>
            $this->status === BookingStatus::ACCEPTE && $user->id === $this->trip->user_id,

            BookingStatus::EN_PAIEMENT =>
            $this->status === BookingStatus::EN_ATTENTE && $user->id === $this->user_id,

            BookingStatus::CONFIRMEE =>
            $this->status === BookingStatus::EN_PAIEMENT, // système ou paiement auto

            BookingStatus::LIVREE =>
            $this->status === BookingStatus::CONFIRMEE && $user->id === $this->trip->user_id,

            BookingStatus::EN_LITIGE =>
            in_array($this->status, [BookingStatus::CONFIRMEE, BookingStatus::LIVREE]),

            BookingStatus::PAIEMENT_ECHOUE =>
            $this->status === BookingStatus::EN_PAIEMENT,

            BookingStatus::REMBOURSEE =>
            in_array($this->status, [BookingStatus::ANNULE, BookingStatus::EN_LITIGE]),

            BookingStatus::EXPIREE =>
            in_array($this->status, [BookingStatus::EN_ATTENTE, BookingStatus::EN_PAIEMENT]),

            BookingStatus::SUSPENDUE =>
            true, // admin / système

            default => false,
        };
    }

    // ✅ Transition métier avec historique
    public function transitionTo(BookingStatus $newStatus, User $user, ?string $reason = null): bool
    {
        if (! $this->canBeUpdatedTo($newStatus, $user)) {
            return false;
        }

        return DB::transaction(function () use ($newStatus, $user, $reason) {
            // 🧾 Log d’historique
            BookingStatusHistory::log(
                booking: $this,
                old: $this->status,
                new: $newStatus,
                user: $user,
                reason: $reason,
            );

            // 🔁 Mise à jour réelle
            $this->update(['status' => $newStatus]);

            return true;
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BookingItem = lien entre un Booking (réservation) et un Luggage (valise),
 * pour un Trip (trajet) donné, avec une quantité de kg réservée.
 */
class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'luggage_id',
        'trip_id',
        'kg_reserved',
        'price',
    ];

    protected $casts = [
        'kg_reserved' => 'float',
        'price'       => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Le booking (réservation) auquel cet item est lié
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * La valise réservée dans cette sous-réservation
     */
    public function luggage(): BelongsTo
    {
        return $this->belongsTo(Luggage::class);
    }

    /**
     * Le trajet associé à cette réservation
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Méthodes métier potentielles (💡 si besoin)
    |--------------------------------------------------------------------------
    */

    /**
     * Détermine si la réservation est dépassée par rapport au poids
     */
    public function isOverweight(): bool
    {
        return $this->kg_reserved > $this->luggage?->weight_kg;
    }

    /**
     * Calcule le tarif par kg (utile pour affichage ou contrôle)
     */
    public function pricePerKg(): float
    {
        return $this->kg_reserved > 0
            ? round($this->price / $this->kg_reserved, 2)
            : 0.0;
    }
}

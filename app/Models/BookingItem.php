<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BookingItem = sous-composant d’une réservation (Booking),
 * représentant une valise (Luggage) réservée sur un trajet (Trip),
 * avec poids et prix associés.
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
    | 🔗 Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Réservation globale à laquelle appartient ce segment
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * 🔗 Valise concernée par cette réservation partielle
     */
    public function luggage(): BelongsTo
    {
        return $this->belongsTo(Luggage::class);
    }

    /**
     * 🔗 Trajet sur lequel cette valise est réservée
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Helpers Métier
    |--------------------------------------------------------------------------
    */

    /**
     * 📦 Est-ce que le poids réservé dépasse la valise ?
     */
    public function isOverweight(): bool
    {
        return $this->luggage && $this->kg_reserved > $this->luggage->weight_kg;
    }

    /**
     * 💰 Calcule le tarif par kg (2 décimales)
     */
    public function pricePerKg(): float
    {
        return $this->kg_reserved > 0
            ? round($this->price / $this->kg_reserved, 2)
            : 0.0;
    }

    /**
     * 🧪 Helper : est valide au niveau poids et cohérence
     */
    public function isValidBooking(): bool
    {
        return $this->luggage
            && $this->kg_reserved > 0
            && $this->kg_reserved <= $this->luggage->weight_kg;
    }
}

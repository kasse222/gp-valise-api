<?php

namespace App\Models;

use App\Enums\TripTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'departure',
        'destination',
        'date',
        'capacity',
        'status',
        'type_trip',
        'flight_number',
    ];

    protected $casts = [
        'date' => 'datetime',
        'type_trip' => TripTypeEnum::class,
        'capacity' => 'float',
    ];

    /**
     * Voyageur ayant proposé ce trajet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Réservations liées à ce trajet
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Colis associés via BookingItem
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Coordonnées GPS ou étapes liées au trajet
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Scope pour les trajets actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'actif');
    }

    /**
     * Le trajet est-il expiré ?
     */
    public function isClosed(): bool
    {
        return $this->date instanceof Carbon && $this->date->isPast();
    }

    /**
     * Peut-on ajouter un certain poids à ce trajet ?
     */
    public function canAcceptKg(float $kg): bool
    {
        $reservedKg = $this->bookings()
            ->where('status', 'confirmee') // ✅ prévoir constante si BookingStatusEnum dispo
            ->with('bookingItems')
            ->get()
            ->flatMap->bookingItems
            ->sum('kg_reserved');

        return ($reservedKg + $kg) <= $this->capacity;
    }

    /**
     * Retourne le poids total déjà réservé
     */
    public function totalKgReserved(): float
    {
        return $this->bookings()
            ->where('status', 'confirmee')
            ->with('bookingItems')
            ->get()
            ->flatMap->bookingItems
            ->sum('kg_reserved');
    }
}

<?php

namespace App\Models;

use App\Status\LuggageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Luggage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'weight_kg',
        'dimensions',
        'pickup_city',
        'delivery_city',
        'pickup_date',
        'delivery_date',
        'status',
    ];

    protected $casts = [
        'pickup_date'   => 'datetime',
        'delivery_date' => 'datetime',
        'status'        => LuggageStatus::class,
        'weight_kg'     => 'float',
    ];

    /**
     * 🔗 L’expéditeur (propriétaire de la valise)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Liaisons multiples via BookingItems (si la valise est partagée)
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * 🎯 Scope pour filtrer les valises disponibles
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('status', LuggageStatus::EN_ATTENTE);
    }

    /**
     * ✅ Cette valise peut-elle être réservée ?
     */
    public function canBeReserved(): bool
    {
        return $this->status->isReservable(); // Enum LuggageStatus doit avoir cette méthode
    }

    /**
     * 📦 Est-ce que la valise est prête à être livrée ?
     */
    public function isDeliverable(): bool
    {
        return $this->delivery_date instanceof Carbon
            && $this->delivery_date->isTodayOrPast();
    }
}

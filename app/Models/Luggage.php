<?php

namespace App\Models;

use App\Status\LuggageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'pickup_date'   => 'date',
        'delivery_date' => 'date',
        'status'         => LuggageStatus::class,

    ];

    /**
     * 🔗 L’expéditeur (propriétaire de la valise)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Liaisons multiples via BookingItems (si la valise est partagée)
     */
    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * 🎯 Scope pour filtrer les valises disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('status', 'en_attente');
    }

    public function canBeReserved(): bool
    {
        return $this->status->isReservable();
    }
}

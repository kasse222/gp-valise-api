<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'luggage_id',
        'status',
        'notes',
    ];

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
     * Get the luggage associated with the booking.
     */
    public function luggage()
    {
        return $this->belongsTo(Luggage::class);
    }

    /**
     * Get the payment associated with this booking.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
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
    public function scopeAcceptees($query)
    {
        return $query->where('status', 'acceptee');
    }

    /**
     * Scope for refused bookings.
     */
    public function scopeRefusees($query)
    {
        return $query->where('status', 'refusee');
    }
    //centralise la règle métier
    public function canBeConfirmed(): bool
    {
        return $this->status === 'en_attente' && $this->trip && $this->trip->user_id === auth()->id();
    }
}

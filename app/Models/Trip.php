<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'flight_number', // ✈️ Pour les trajets aériens
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * 🔗 Lien vers l'utilisateur (voyageur) propriétaire du trajet
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Toutes les réservations (bookings) sur ce trajet
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * 🔗 Toutes les Ajouter les relations inverses sur ce trajet
     */
    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * 🛰️ Liste des coordonnées GPS liées à ce trajet
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
}

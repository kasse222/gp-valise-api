<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

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

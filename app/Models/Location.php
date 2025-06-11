<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [

        'user_id',        // 🔗 Trajet concerné
        'trip_id',
        'latitude',       // 🌍 Coordonnée Y
        'longitude',      // 🌍 Coordonnée X
        'recorded_at',    // ⏱️ Date/heure de la position
    ];

    /**
     * 🔗 Le trajet auquel appartient cette position
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    // 🛑 PAS BESOIN de user_id sauf si plusieurs users peuvent tracer des coords
}

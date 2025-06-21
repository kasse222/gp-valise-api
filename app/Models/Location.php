<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'latitude',
        'longitude',
        'city',
        'order_index',
    ];

    protected $casts = [
        'latitude'     => 'float',
        'longitude'    => 'float',
        'order_index'  => 'integer',
    ];

    /**
     * 🔗 Trajet auquel cette étape appartient
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * 🔁 Scope : ordonné par position dans le trajet
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}

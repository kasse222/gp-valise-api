<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id',
        'luggage_id',
        'trip_id',
        'kg_reserved',
        'price',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function luggage()
    {
        return $this->belongsTo(Luggage::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}

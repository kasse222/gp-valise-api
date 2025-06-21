<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function luggage(): BelongsTo
    {
        return $this->belongsTo(Luggage::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}

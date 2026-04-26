<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BookingItem extends Model
{
    use HasFactory;

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
    public function reports(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }



    public function isOverweight(): bool
    {
        return $this->luggage && $this->kg_reserved > $this->luggage->weight_kg;
    }

    public function pricePerKg(): float
    {
        return $this->kg_reserved > 0
            ? round($this->price / $this->kg_reserved, 2)
            : 0.0;
    }

    public function isValidBooking(): bool
    {
        return $this->luggage
            && $this->kg_reserved > 0
            && $this->kg_reserved <= $this->luggage->weight_kg;
    }
}

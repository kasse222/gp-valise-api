<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'latitude',
        'longitude',
        'approximate_latitude',
        'approximate_longitude',
        'address',
        'city',
        'instructions',
    ];

    protected $casts = [
        'latitude'             => 'float',
        'longitude'            => 'float',
        'approximate_latitude' => 'float',
        'approximate_longitude' => 'float',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isRevealedFor(Booking $booking): bool
    {
        return $booking->status === BookingStatusEnum::CONFIRMEE
            || $booking->status === BookingStatusEnum::LIVREE
            || $booking->status === BookingStatusEnum::TERMINE;
    }
}

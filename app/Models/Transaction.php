<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'booking_id',
        'type',
        'amount',
        'status',
        'currency',
        'snapshot'
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }


    public static function createFromBooking(Booking $booking): self
    {
        $amount = $booking->calculateAmount();
        $commission = $booking->user->plan?->getCommissionPercent() ?? 0;

        return self::create([
            'booking_id'         => $booking->id,
            'user_id'            => $booking->user_id,
            'amount'             => $amount,
            'commission_percent' => $commission,
            'status'             => 'payee',
        ]);
    }
}

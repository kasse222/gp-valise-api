<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'amount',
        'status',
        'provider',
        'reference',
        'paid_at',
    ];

    /**
     * Get the booking related to this payment.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope for paid payments.
     */
    public function scopePayes($query)
    {
        return $query->where('status', 'paye');
    }

    /**
     * Scope for pending payments.
     */
    public function scopeEnAttente($query)
    {
        return $query->where('status', 'en_attente');
    }
}

<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'amount',
        'method',     // Ex: card, stripe, cash
        'status',     // Ex: pending, paid, failed
        'paid_at',
    ];

    protected $casts = [
        'status' => PaymentStatusEnum::class,
        'paid_at' => 'datetime',
        'amount'  => 'float',
        'method' => PaymentMethodEnum::class,
    ];

    /**
     * 🔗 Utilisateur ayant payé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Réservation liée au paiement
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * 💳 Paiement finalisé ?
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}

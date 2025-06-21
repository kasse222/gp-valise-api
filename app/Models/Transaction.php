<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'amount',
        'currency',
        'status',     // ✅ Prévoir EnumTransactionStatus
        'method',     // ✅ Prévoir EnumPaymentMethod
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'amount'       => 'float',
    ];

    /**
     * 🔗 Utilisateur ayant effectué le paiement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Réservation concernée (optionnelle)
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * ✅ La transaction a-t-elle été traitée ?
     */
    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    /**
     * 💡 Prévoir une méthode d’état si Enums en place
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
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
        'method',
        'status',
        'currency',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'amount'   => 'float',
        'paid_at'  => 'datetime',
        'currency' => CurrencyEnum::class,
        'status'   => PaymentStatusEnum::class,
        'method'   => PaymentMethodEnum::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Méthodes métier
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->status === PaymentStatusEnum::SUCCES;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatusEnum::EN_COURS;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatusEnum::ECHEC;
    }

    public function markAsPaid(): void
    {
        $this->status = PaymentStatusEnum::SUCCES;
        $this->paid_at = now();
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | 🔎 Query scopes
    |--------------------------------------------------------------------------
    */

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatusEnum::SUCCES);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatusEnum::EN_COURS);
    }
}

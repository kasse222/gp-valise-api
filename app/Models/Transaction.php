<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
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
        'status',         // ✅ TransactionStatusEnum
        'method',         // ✅ PaymentMethodEnum
        'processed_at',
    ];

    protected $casts = [
        'amount'        => 'float',
        'currency'      => CurrencyEnum::class,
        'status'        => TransactionStatusEnum::class,
        'method'        => PaymentMethodEnum::class,
        'processed_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
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
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatusEnum::PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === TransactionStatusEnum::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatusEnum::FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === TransactionStatusEnum::REFUNDED;
    }

    public function isCancelable(): bool
    {
        return $this->status->isCancelable();
    }

    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }

    public function label(): string
    {
        return "{$this->method->label()} - {$this->amount} {$this->currency}";
    }

    public function badge(): array
    {
        return $this->status->badge();
    }
}

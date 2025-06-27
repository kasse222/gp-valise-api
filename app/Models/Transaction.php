<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
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
        'status',        // ✅ Enum TransactionStatusEnum
        'method',        // ✅ Enum PaymentMethodEnum
        'processed_at',
    ];

    protected $casts = [
        'amount'       => 'float',
        'processed_at' => 'datetime',
        'status'       => TransactionStatusEnum::class,
        'method'       => PaymentMethodEnum::class,
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

    public function label(): string
    {
        return "{$this->method->label()} - {$this->amount} {$this->currency}";
    }
}

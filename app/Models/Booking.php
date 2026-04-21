<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'trip_id',
        'status',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'expired_at',
        'payment_expires_at',
        'comment',
    ];

    protected $casts = [
        'status' => BookingStatusEnum::class,
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'payment_expires_at' => 'datetime',
    ];

    protected static bool $disableStatusAutoCreate = false;

    public static function disableAutoStatusCreation(): void
    {
        static::$disableStatusAutoCreate = true;
    }

    protected static function booted(): void
    {
        static::created(function (Booking $booking) {
            if (self::$disableStatusAutoCreate) {
                return;
            }

            $booking->statusHistories()->create([
                'old_status' => null,
                'new_status' => $booking->status,
                'changed_by' => $booking->user_id,
                'reason' => 'Création initiale',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function items(): HasMany
    {
        return $this->bookingItems();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * Compat legacy : certains flux regardent encore la charge principale.
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class)
            ->where('type', TransactionTypeEnum::CHARGE);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    public function statusIs(BookingStatusEnum $expected): bool
    {
        return $this->status === $expected;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function canTransitionTo(BookingStatusEnum $to): bool
    {
        return $this->status->canTransitionTo($to);
    }

    public function transitionTo(BookingStatusEnum $newStatus, ?User $changer = null, ?string $reason = null): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Transition non autorisée de {$this->status->value} vers {$newStatus->value}"
            );
        }

        match ($newStatus) {
            BookingStatusEnum::CONFIRMEE => $this->confirmed_at = now(),
            BookingStatusEnum::LIVREE,
            BookingStatusEnum::TERMINE => $this->completed_at = now(),
            BookingStatusEnum::ANNULE => $this->cancelled_at = now(),
            BookingStatusEnum::EXPIREE => $this->expired_at = now(),
            default => null,
        };

        if ($newStatus !== BookingStatusEnum::EN_PAIEMENT) {
            $this->payment_expires_at = match ($newStatus) {
                BookingStatusEnum::CONFIRMEE,
                BookingStatusEnum::ANNULE,
                BookingStatusEnum::EXPIREE,
                BookingStatusEnum::PAIEMENT_ECHOUE => null,
                default => $this->payment_expires_at,
            };
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        $this->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changer?->id,
            'reason' => $reason ?? 'Changement programmatique',
        ]);
    }

    public function canBeUpdatedTo(BookingStatusEnum $newStatus, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $travelerId = $this->trip?->user_id;
        $senderId = $this->user_id;

        return match (true) {
            $this->status === BookingStatusEnum::EN_PAIEMENT
                && $newStatus === BookingStatusEnum::ANNULE
                && $user->id === $senderId => true,

            $this->status === BookingStatusEnum::EN_PAIEMENT
                && $newStatus === BookingStatusEnum::CONFIRMEE
                && $user->id === $travelerId => true,

            $this->status === BookingStatusEnum::CONFIRMEE
                && $newStatus === BookingStatusEnum::LIVREE
                && $user->id === $travelerId => true,

            $this->status === BookingStatusEnum::LIVREE
                && $newStatus === BookingStatusEnum::EN_LITIGE
                && in_array($user->id, [$senderId, $travelerId], true) => true,

            default => false,
        };
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatusEnum::CONFIRMEE;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatusEnum::ANNULE;
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status->canBeConfirmed();
    }

    public function canBeDelivered(): bool
    {
        return $this->status->canBeDelivered();
    }

    public function canBeDisputed(): bool
    {
        return $this->status->canBeDisputed();
    }

    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }

    public function isPaymentExpired(): bool
    {
        return $this->status === BookingStatusEnum::EN_PAIEMENT
            && $this->payment_expires_at !== null
            && $this->payment_expires_at->isPast();
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === BookingStatusEnum::EN_PAIEMENT
            && $this->payment_expires_at?->isFuture();
    }

    public function hasPayoutTransaction(): bool
    {
        return $this->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->exists();
    }

    public function hasRefundTransaction(): bool
    {
        return $this->transactions()
            ->where('type', TransactionTypeEnum::REFUND)
            ->exists();
    }

    public function hasCompletedChargeTransaction(): bool
    {
        return $this->transactions()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->exists();
    }

    public function canTriggerPayout(): bool
    {
        return $this->status === BookingStatusEnum::LIVREE
            && $this->hasCompletedChargeTransaction()
            && ! $this->hasPayoutTransaction();
    }

    public function canTriggerRefund(): bool
    {
        return in_array($this->status, [
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::LIVREE,
            BookingStatusEnum::EN_LITIGE,
        ], true)
            && $this->hasCompletedChargeTransaction()
            && ! $this->hasRefundTransaction()
            && ! $this->hasPayoutTransaction();
    }

    public function hasSuccessfulChargeTransaction(): bool
    {
        return $this->transactions()
            ->where('type', \App\Enums\TransactionTypeEnum::CHARGE)
            ->where('status', \App\Enums\TransactionStatusEnum::COMPLETED)
            ->exists();
    }

    public function refundableAmount(): float
    {
        $charge = $this->transactions()
            ->where('type', \App\Enums\TransactionTypeEnum::CHARGE)
            ->where('status', \App\Enums\TransactionStatusEnum::COMPLETED)
            ->sum('amount');

        $refunds = $this->transactions()
            ->where('type', \App\Enums\TransactionTypeEnum::REFUND)
            ->sum('amount');

        return max(0, $charge - $refunds);
    }
}

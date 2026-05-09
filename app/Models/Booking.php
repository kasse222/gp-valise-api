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
        'delivered_at',
        'escrow_releasable_at',
        'disputed_at',
    ];

    protected $casts = [
        'status' => BookingStatusEnum::class,
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'payment_expires_at' => 'datetime',
        'delivered_at'        => 'datetime',
        'escrow_releasable_at' => 'datetime',
        'disputed_at'         => 'datetime',
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

    public function isEscrowReleasable(): bool
    {
        return $this->status === BookingStatusEnum::LIVREE
            && $this->escrow_releasable_at !== null
            && $this->escrow_releasable_at->isPast()
            && $this->disputed_at === null;
    }

    public function hasActiveDispute(): bool
    {
        return $this->disputed_at !== null;
    }

    public function markDelivered(): void
    {
        $delayHours = config('gpvalise.escrow_delay_hours', 48);

        $this->delivered_at         = now();
        $this->escrow_releasable_at = now()->addHours($delayHours);
        $this->save();
    }

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

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class)
            ->where('type', TransactionTypeEnum::CHARGE);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reports(): HasMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

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

    public function transitionTo(
        BookingStatusEnum $newStatus,
        ?User $changer = null,
        ?string $reason = null
    ): void {
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

        if (in_array($newStatus, [
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::ANNULE,
            BookingStatusEnum::EXPIREE,
            BookingStatusEnum::PAIEMENT_ECHOUE,
        ], true)) {
            $this->payment_expires_at = null;
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

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatusEnum::CONFIRMEE;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatusEnum::ANNULE;
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

    public function hasSuccessfulChargeTransaction(): bool
    {
        return $this->hasCompletedChargeTransaction();
    }
}

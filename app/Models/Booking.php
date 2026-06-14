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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'trip_id',
        'status',
        'comment',

        // Destinataire (obligatoire à la réservation — Instant Booking)
        'recipient_name',
        'recipient_phone',
        'recipient_email',

        // Remise / livraison
        'handed_over_at',
        'delivery_code',
        'delivery_qr_token',

        // Annulation
        'cancel_reason',
        'refund_rate',

        // Timestamps financiers / logistiques
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'expired_at',
        'payment_expires_at',
        'delivered_at',
        'escrow_releasable_at',
        'disputed_at',
    ];

    protected $casts = [
        'status'               => BookingStatusEnum::class,
        'confirmed_at'         => 'datetime',
        'completed_at'         => 'datetime',
        'cancelled_at'         => 'datetime',
        'expired_at'           => 'datetime',
        'payment_expires_at'   => 'datetime',
        'delivered_at'         => 'datetime',
        'escrow_releasable_at' => 'datetime',
        'disputed_at'          => 'datetime',
        'handed_over_at'       => 'datetime',
        'refund_rate'          => 'integer',
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
                'reason'     => 'Création initiale',
            ]);
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

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

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function pickupLocation(): HasOne
    {
        return $this->hasOne(PickupLocation::class);
    }

    // ── Transitions ───────────────────────────────────────────────────────────

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

        $oldStatus = $this->status;
        $now       = now();

        match ($newStatus) {
            BookingStatusEnum::EN_PAIEMENT => $this->markAwaitingPayment($now),
            BookingStatusEnum::CONFIRMEE   => $this->confirmed_at = $now,
            BookingStatusEnum::EN_TRANSIT  => $this->handed_over_at = $now,
            BookingStatusEnum::TERMINE     => $this->completed_at = $now,
            BookingStatusEnum::ANNULE      => $this->cancelled_at = $now,
            BookingStatusEnum::EXPIREE     => $this->expired_at = $now,
            default                        => null,
        };

        // Nettoyer payment_expires_at dès que le paiement est résolu
        if (in_array($newStatus, [
            BookingStatusEnum::CONFIRMEE,
            BookingStatusEnum::ANNULE,
            BookingStatusEnum::EXPIREE,
            BookingStatusEnum::PAIEMENT_ECHOUE,
        ], true)) {
            $this->payment_expires_at = null;
        }

        $this->status = $newStatus;
        $this->save();

        $this->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changer?->id,
            'reason'     => $reason ?? 'Changement programmatique',
        ]);
    }

    private function markAwaitingPayment(\Illuminate\Support\Carbon $now): void
    {
        $this->payment_expires_at = $now->copy()->addMinutes(
            config('gpvalise.payment_expiration_minutes', 30)
        );
    }

    // ── Lifecycle helpers ─────────────────────────────────────────────────────

    /**
     * Appelé lors de la remise physique (CONFIRMEE → EN_TRANSIT).
     * Génère le code secret et le QR token envoyés au destinataire.
     */
    public function markHandedOver(): void
    {
        $this->delivery_code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->delivery_qr_token  = \Illuminate\Support\Str::uuid()->toString();
        $this->handed_over_at     = now();
        $this->save();
    }

    /**
     * Appelé lors du scan QR / saisie code par le destinataire (EN_TRANSIT → LIVREE).
     */
    public function markDelivered(): void
    {
        $now         = now();
        $delayHours  = config('gpvalise.escrow_delay_hours', 48);

        $this->delivered_at         = $now;
        $this->escrow_releasable_at = $now->copy()->addHours($delayHours);
        $this->save();
    }

    // ── Annulation avec règle de remboursement ────────────────────────────────

    /**
     * Détermine le taux de remboursement selon les règles métier.
     * Appelé avant transitionTo(ANNULE) pour enregistrer refund_rate.
     */
    public function computeRefundRate(string $cancelledBy): int
    {
        // Annulation par le traveler → 100% toujours
        if ($cancelledBy === 'traveler') {
            return 100;
        }

        // Pas encore confirmé (paiement en cours) → 100%
        if ($this->status === BookingStatusEnum::EN_PAIEMENT) {
            return 100;
        }

        // Annulation sender depuis CONFIRMEE
        $trip = $this->trip;

        if ($trip === null || $trip->date === null) {
            return 100; // cas défensif
        }

        $hoursUntilDeparture = now()->diffInHours($trip->date, false);

        return match (true) {
            $hoursUntilDeparture > 48  => 100,
            $hoursUntilDeparture >= 0  => 70,
            default                    => 0,  // no-show (date passée)
        };
    }

    // ── Predicates ────────────────────────────────────────────────────────────

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

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatusEnum::CONFIRMEE;
    }

    public function isInTransit(): bool
    {
        return $this->status === BookingStatusEnum::EN_TRANSIT;
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

    public function canEnterDispute(): bool
    {
        return $this->status->canEnterDispute();
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

    /**
     * Vérifie que le code secret présenté par le destinataire est valide.
     */
    public function verifyDeliveryCode(string $code): bool
    {
        return $this->delivery_code !== null
            && hash_equals($this->delivery_code, $code);
    }

    /**
     * Vérifie que le QR token présenté par le destinataire est valide.
     */
    public function verifyDeliveryQrToken(string $token): bool
    {
        return $this->delivery_qr_token !== null
            && hash_equals($this->delivery_qr_token, $token);
    }

    // ── @deprecated — conservés pour compat tests legacy ─────────────────────

    /** @deprecated Instant Booking */
    public function isPendingApproval(): bool
    {
        return $this->status === BookingStatusEnum::PENDING_APPROVAL;
    }

    /** @deprecated Instant Booking */
    public function isDeclinedByTraveler(): bool
    {
        return $this->status === BookingStatusEnum::DECLINED_BY_TRAVELER;
    }

    /** @deprecated Instant Booking */
    public function canBeApprovedByTraveler(): bool
    {
        return false;
    }

    /** @deprecated Instant Booking */
    public function canBeDeclinedByTraveler(): bool
    {
        return false;
    }
}

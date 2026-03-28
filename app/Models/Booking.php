<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Models\BookingStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
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
        'payment_expires_at',
        'comment',
    ];

    protected $casts = [
        'status'             => BookingStatusEnum::class,
        'confirmed_at'       => 'datetime',
        'completed_at'       => 'datetime',
        'cancelled_at'       => 'datetime',
        'payment_expires_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Boot : audit du statut initial
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relations
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

    /**
     * Alias de la relation canonique bookingItems (compatibilité).
     */
    public function items(): HasMany
    {
        return $this->bookingItems();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Méthodes Métier : statut
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
            throw new \DomainException("❌ Transition non autorisée de {$this->status->value} → {$newStatus->value}");
        }

        // Timestamps associés
        match ($newStatus) {
            BookingStatusEnum::CONFIRMEE => $this->confirmed_at = now(),
            BookingStatusEnum::TERMINE   => $this->completed_at = now(),
            BookingStatusEnum::ANNULE    => $this->cancelled_at = now(),
            default                      => null,
        };

        $oldStatus = $this->status;
        $this->status = $newStatus->value; // 👈 fix ici
        $this->save();


        $this->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changer?->id ?? auth::id(),
            'reason'     => $reason ?? 'Changement programmatique',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔐 Vérification des droits métier selon utilisateur
    |--------------------------------------------------------------------------
    */

    public function canBeUpdatedTo(BookingStatusEnum $newStatus, ?User $user = null): bool
    {

        $user ??= Auth::user();

        return match (true) {
            // Expéditeur peut annuler une résa en attente ou acceptée
            $this->status === BookingStatusEnum::EN_ATTENTE
                && $newStatus === BookingStatusEnum::ANNULE
                && $user->id === $this->user_id => true,

            // Voyageur peut confirmer
            $this->status === BookingStatusEnum::EN_ATTENTE
                && $newStatus === BookingStatusEnum::CONFIRMEE
                && $user->id === $this->trip->user_id => true,

            // Livraison possible par le voyageur
            $this->status === BookingStatusEnum::CONFIRMEE
                && $newStatus === BookingStatusEnum::LIVREE
                && $user->id === $this->trip->user_id => true,

            default => false,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | 📄 Aliases lisibles
    |--------------------------------------------------------------------------
    */

    public function isConfirmed(): bool
    {
        return $this->is(BookingStatusEnum::CONFIRMEE);
    }

    public function isCancelled(): bool
    {
        return $this->is(BookingStatusEnum::ANNULE);
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
    public function reports(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}

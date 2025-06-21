<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Validators\BookingStatusValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'comment',
    ];

    protected $casts = [
        'status'         => BookingStatusEnum::class,
        'confirmed_at'   => 'datetime',
        'completed_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    // 🔗 Relations

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

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    // 🧠 Logique métier

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatusEnum::CONFIRMEE;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatusEnum::ANNULE;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
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

    /**
     * ✅ Vérifie si une transition est autorisée
     */
    public function canTransitionTo(BookingStatusEnum $to): bool
    {
        return $this->status->canTransitionTo($to);
    }


    /**
     * 🚀 Applique une transition de statut (si autorisée)
     */
    public function transitionTo(BookingStatusEnum $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \DomainException("Transition non autorisée de {$this->status->value} vers {$newStatus->value}");
        }

        match ($newStatus) {
            BookingStatusEnum::CONFIRMEE => $this->confirmed_at = now(),
            BookingStatusEnum::TERMINE   => $this->completed_at = now(),
            BookingStatusEnum::ANNULE    => $this->cancelled_at = now(),
            default                      => null,
        };

        $this->status = $newStatus;
        $this->save();

        $this->statusHistories()->create([
            'status' => $newStatus,
        ]);
    }
}

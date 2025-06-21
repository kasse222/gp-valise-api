<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'status' => BookingStatusEnum::class,
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relations

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

    // Méthodes métier

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatusEnum::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatusEnum::CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [
            BookingStatusEnum::COMPLETED,
            BookingStatusEnum::CANCELLED,
        ], true);
    }

    public function transitionTo(BookingStatusEnum $newStatus): void
    {
        // On vérifie que la transition est valide (à implémenter dans Enum ou Validator)

        $this->status = $newStatus;
        match ($newStatus) {
            BookingStatusEnum::CONFIRMED => $this->confirmed_at = now(),
            BookingStatusEnum::COMPLETED => $this->completed_at = now(),
            BookingStatusEnum::CANCELLED => $this->cancelled_at = now(),
            default => null,
        };

        $this->save();

        $this->statusHistories()->create([
            'status' => $newStatus,
        ]);
    }
}

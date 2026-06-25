<?php

namespace App\Models;

use App\Actions\Booking\CanBeReserved;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'departure',
        'destination',
        'user_id',
        'date',
        'capacity',
        'status',
        'type_trip',
        'flight_number',
        'price_per_kg',
        'currency',
        'pickup_address',
        'pickup_city',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_approx_latitude',
        'pickup_approx_longitude',
        'pickup_instructions',
        'delivery_address',
        'delivery_city',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_approx_latitude',
        'delivery_approx_longitude',
        'delivery_instructions',
    ];

    protected $casts = [
        'date'        => 'datetime',
        'status'     => TripStatusEnum::class,
        'type_trip'  => TripTypeEnum::class,
        'capacity'    => 'integer',
        'price_per_kg' => 'integer',
        'currency'     => CurrencyEnum::class,

    ];
    public function departureLocation(): HasOne
    {
        return $this->hasOne(Location::class)->where('order_index', 0);
    }

    public function destinationLocation(): HasOne
    {
        return $this->hasOne(Location::class)->orderByDesc('order_index');
    }



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderBy('order_index');
    }
    public function reports(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }


    public function isPast(): bool
    {
        return $this->date?->isPast() ?? false;
    }

    public function isReservable(): bool
    {
        return CanBeReserved::handle($this);
    }

    public function canAcceptGrams(int $grams): bool
    {
        return ($this->gramsReserved() + $grams) <= $this->capacity;
    }

    public function gramsReserved(): int
    {
        $now = now();

        return (int) $this->bookingItems()
            ->whereHas('booking', function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->where('status', BookingStatusEnum::CONFIRMEE->value)
                        ->orWhere('status', BookingStatusEnum::PENDING_APPROVAL->value)
                        ->orWhere(function ($subQuery) use ($now) {
                            $subQuery->where('status', BookingStatusEnum::EN_PAIEMENT->value)
                                ->whereNotNull('payment_expires_at')
                                ->where('payment_expires_at', '>', $now);
                        });
                });
            })
            ->sum('kg_reserved');
    }

    public function gramsDisponible(): int
    {
        return max(0, $this->capacity - $this->gramsReserved());
    }

    public function isClosed(): bool
    {
        return $this->status?->isFinal() ?? false;
    }



    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TripStatusEnum::ACTIVE);
    }

    public function scopeReservable(Builder $query): Builder
    {
        $now = now();

        return $query->whereIn('status', [TripStatusEnum::ACTIVE, TripStatusEnum::PENDING])
            ->where('date', '>', $now)
            ->whereRaw('
            (
                SELECT COALESCE(SUM(booking_items.kg_reserved), 0)
                FROM bookings
                JOIN booking_items ON booking_items.booking_id = bookings.id
                WHERE bookings.trip_id = trips.id
                AND (
                    bookings.status = ?
                    OR bookings.status = ?
                    OR (
                        bookings.status = ?
                        AND bookings.payment_expires_at IS NOT NULL
                        AND bookings.payment_expires_at > ?
                    )
                )
            ) < trips.capacity
        ', [
                BookingStatusEnum::CONFIRMEE->value,
                BookingStatusEnum::PENDING_APPROVAL->value,
                BookingStatusEnum::EN_PAIEMENT->value,
                $now,
            ]);
    }
}

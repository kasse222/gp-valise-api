<?php

namespace App\Models;

use App\Actions\Booking\CanBeReserved;
use App\Enums\BookingStatusEnum;
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
        'user_id',
        'date',
        'capacity',
        'status',
        'type_trip',
        'flight_number',
        'price_per_kg'
    ];

    protected $casts = [
        'date'        => 'datetime',
        'status'     => TripStatusEnum::class,
        'type_trip'  => TripTypeEnum::class,
        'capacity'    => 'float',
        'price_per_kg' => 'decimal:2',

    ];
    public function departureLocation(): HasOne
    {
        return $this->hasOne(Location::class)->where('order_index', 0);
    }

    public function destinationLocation(): HasOne
    {
        return $this->hasOne(Location::class)->orderByDesc('order_index');
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

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Logique métier
    |--------------------------------------------------------------------------
    */

    public function isPast(): bool
    {
        return $this->date?->isPast() ?? false;
    }

    public function isReservable(): bool
    {
        return CanBeReserved::handle($this);
    }

    public function canAcceptKg(float $kg): bool
    {
        return ($this->kgReserved() + $kg) <= $this->capacity;
    }

    public function kgReserved(): float
    {
        // Évite un `get()` coûteux
        return $this->bookings()
            ->where('status', BookingStatusEnum::CONFIRMEE)
            ->withSum('bookingItems as total_reserved', 'kg_reserved')
            ->get()
            ->sum('total_reserved');
    }

    public function kgDisponible(): float
    {
        return max(0, $this->capacity - $this->kgReserved());
    }

    /*
    |--------------------------------------------------------------------------
    | 🔍 Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TripStatusEnum::ACTIVE);
    }

    public function scopeReservable(Builder $query): Builder
    {
        return $query->where('status', TripStatusEnum::ACTIVE)
            ->whereDate('date', '>=', now())
            ->whereRaw('
                (
                    SELECT COALESCE(SUM(booking_items.kg_reserved), 0)
                    FROM bookings
                    JOIN booking_items ON booking_items.booking_id = bookings.id
                    WHERE bookings.trip_id = trips.id
                    AND bookings.status = ?
                ) < trips.capacity
            ', [BookingStatusEnum::CONFIRMEE->value]);
    }
}

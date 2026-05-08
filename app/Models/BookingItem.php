<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'luggage_id',
        'trip_id',
        'kg_reserved', // grammes
        'price',       // centimes
    ];

    protected function casts(): array
    {
        return [
            'kg_reserved' => 'integer', // ← float → integer (grammes)
            'price'       => 'integer', // ← float → integer (centimes)
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function luggage(): BelongsTo
    {
        return $this->belongsTo(Luggage::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isOverweight(): bool
    {
        return $this->luggage && $this->kg_reserved > $this->luggage->weight_kg;
    }

    public function pricePerGram(): int
    {
        return $this->kg_reserved > 0
            ? (int) round($this->price / $this->kg_reserved)
            : 0;
    }

    public function isValidBooking(): bool
    {
        return $this->luggage
            && $this->kg_reserved > 0
            && $this->kg_reserved <= $this->luggage->weight_kg;
    }
}

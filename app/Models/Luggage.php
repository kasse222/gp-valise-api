<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LuggageCategoryEnum;
use App\Enums\LuggageStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class Luggage extends Model
{
    use HasFactory;

    protected $table = 'luggages';

    protected $fillable = [
        'user_id',
        'trip_id',
        'description',
        'category',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'volume_cm3',
        'pickup_city',
        'delivery_city',
        'pickup_date',
        'delivery_date',
        'status',
        'tracking_id',
        'is_fragile',
        'insurance_requested',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date'         => 'datetime',
            'delivery_date'       => 'datetime',
            'status'              => LuggageStatusEnum::class,
            'category'            => LuggageCategoryEnum::class,
            'weight_kg'           => 'integer',
            'length_cm'           => 'integer',
            'width_cm'            => 'integer',
            'height_cm'           => 'integer',
            'volume_cm3'          => 'integer',
            'is_fragile'          => 'boolean',
            'insurance_requested' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $luggage): void {
            if (empty($luggage->tracking_id)) {
                $luggage->tracking_id = Str::uuid()->toString();
            }
        });

        static::saving(function (self $luggage): void {
            if ($luggage->length_cm && $luggage->width_cm && $luggage->height_cm) {
                $luggage->volume_cm3 = $luggage->length_cm * $luggage->width_cm * $luggage->height_cm;
            }
        });
    }

    public function weightKgDisplay(): float
    {
        return $this->weight_kg / 10;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
    public function isDisponible(): bool
    {
        return $this->status === LuggageStatusEnum::EN_ATTENTE;
    }
    public function isAvailable(): bool
    {
        return $this->status === LuggageStatusEnum::EN_ATTENTE;
    }
    public function isFinal(): bool
    {
        return $this->status?->isFinal() ?? false;
    }
    public function isCancelled(): bool
    {
        return $this->status === LuggageStatusEnum::ANNULEE;
    }
}

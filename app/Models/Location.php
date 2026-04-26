<?php

namespace App\Models;

use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'latitude',
        'longitude',
        'city',
        'position',
        'order_index',
        'type',
    ];

    protected $casts = [
        'latitude'     => 'float',
        'longitude'    => 'float',
        'order_index'  => 'integer',
        'position'     => LocationPositionEnum::class,
        'type'         => LocationTypeEnum::class,
    ];




    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }




    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_index');
    }



    public function isDeparture(): bool
    {
        return $this->order_index === 0;
    }

    public function isArrival(?int $maxIndex = null): bool
    {
        return $maxIndex !== null && $this->order_index === $maxIndex;
    }

    public function isCustomsCheckpoint(): bool
    {
        return $this->type === LocationTypeEnum::DOUANE;
    }

    public function isHub(): bool
    {
        return $this->type === LocationTypeEnum::HUB;
    }

    public function label(): string
    {
        return "{$this->city} ({$this->latitude}, {$this->longitude})";
    }
}

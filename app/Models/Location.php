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
        'trip_id',       // 🔗 ID du trajet auquel appartient ce point
        'latitude',      // 🌍 Coordonnée latitude
        'longitude',     // 🌍 Coordonnée longitude
        'city',
        'position',         // 🏙️ Ville (optionnelle ou normalisée)
        'order_index',   // 🧭 Position dans le trajet (0 = départ, n = arrivée)
    ];

    protected $casts = [
        'latitude'     => 'float',
        'longitude'    => 'float',
        'order_index'  => 'integer',
        'position'     => LocationPositionEnum::class,
        'type'         => LocationTypeEnum::class,

    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Trajet auquel appartient cette localisation
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * 🔁 Scope : ordonné selon l’ordre du trajet
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_index');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers Métier
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Est-ce la première étape ?
     */
    public function isDeparture(): bool
    {
        return $this->order_index === 0;
    }

    /**
     * ✅ Est-ce la dernière étape ? (si total connu)
     */
    public function isArrival(int $maxIndex): bool
    {
        return $this->order_index === $maxIndex;
    }

    public function isCustomsCheckpoint(): bool
    {
        return $this->type === LocationTypeEnum::DOUANE;
    }

    public function isHub(): bool
    {
        return $this->type === LocationTypeEnum::HUB;
    }
}

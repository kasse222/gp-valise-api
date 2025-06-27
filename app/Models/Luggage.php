<?php

namespace App\Models;

use App\Enums\LuggageStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Luggage extends Model
{
    use HasFactory;

    protected $table = 'luggages';

    protected $fillable = [
        'user_id',
        'description',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'pickup_city',
        'delivery_city',
        'pickup_date',
        'delivery_date',
        'status',
        'tracking_id',
    ];

    protected $casts = [
        'pickup_date'   => 'datetime',
        'delivery_date' => 'datetime',
        'status'        => LuggageStatusEnum::class,
        'weight_kg'     => 'float',
        'length_cm'     => 'float',
        'width_cm'      => 'float',
        'height_cm'     => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot: génération automatique du tracking_id UUID
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        static::creating(function (self $luggage) {
            if (empty($luggage->tracking_id)) {
                $luggage->tracking_id = Str::uuid()->toString();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Propriétaire du bagage
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs personnalisés
    |--------------------------------------------------------------------------
    */

    /**
     * 📦 Volume du bagage (en cm³)
     */
    public function getVolumeCm3Attribute(): ?float
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm) {
            return round($this->length_cm * $this->width_cm * $this->height_cm, 2);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Vérifie si le bagage est dans un état final (livré, perdu, annulé)
     */
    public function isFinal(): bool
    {
        return $this->status?->isFinal() ?? false;
    }

    /**
     * 🔍 Bagage prêt à être réservé ?
     */
    public function isAvailable(): bool
    {
        return $this->status === LuggageStatusEnum::RESERVEE;
    }

    /**
     * 🛑 Bagage annulé ?
     */
    public function isCancelled(): bool
    {
        return $this->status === LuggageStatusEnum::ANNULEE;
    }
}

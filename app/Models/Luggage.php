<?php

namespace App\Models;

use App\Enums\LuggageStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    // 🚀 Boot pour générer un tracking UUID unique si vide
    protected static function booted(): void
    {
        static::creating(function (self $luggage) {
            if (!$luggage->tracking_id) {
                $luggage->tracking_id = Str::uuid();
            }
        });
    }

    // 🔗 Relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 🔎 Accesseur : volume en cm³La méthode getVolumeCm3Attribute()
    // est un accessor Laravel que tu peux appeler avec $luggage->volume_cm3.
    public function getVolumeCm3Attribute(): ?float
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm) {
            return $this->length_cm * $this->width_cm * $this->height_cm;
        }

        return null;
    }

    // ✅ Statut final ?
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}

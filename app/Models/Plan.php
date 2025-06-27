<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'type',
        'features',
        'duration_days',
        'discount_percent',
        'discount_expires_at',
        'is_active',
    ];

    protected $casts = [
        'type'                 => PlanTypeEnum::class,
        'features'             => 'array',
        'discount_expires_at'  => 'datetime',
        'is_active'            => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relations
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Méthodes métier
    |--------------------------------------------------------------------------
    */

    /**
     * Pourcentage de réduction actuel (si encore valable)
     */
    public function getCommissionPercent(): float
    {
        return $this->hasActiveDiscount()
            ? ($this->discount_percent ?? 0)
            : 0;
    }

    /**
     * Le plan a-t-il une réduction valide ?
     */
    public function hasActiveDiscount(): bool
    {
        return $this->discount_expires_at?->isFuture();
    }

    /**
     * Est-ce un plan premium ?
     */
    public function isPremium(): bool
    {
        return $this->type === PlanTypeEnum::PREMIUM;
    }

    /**
     * Est-il actif ?
     */
    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    /*
    |--------------------------------------------------------------------------
    | 🧪 Scope utiles
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithDiscount($query)
    {
        return $query->whereDate('discount_expires_at', '>', now());
    }
}

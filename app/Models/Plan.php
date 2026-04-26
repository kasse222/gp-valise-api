<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

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



    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }



    public function getCommissionPercent(): float
    {
        return $this->hasActiveDiscount()
            ? ($this->discount_percent ?? 0)
            : 0;
    }


    public function hasActiveDiscount(): bool
    {
        return $this->discount_expires_at?->isFuture();
    }


    public function isPremium(): bool
    {
        return $this->type === PlanTypeEnum::PREMIUM;
    }


    public function isAvailable(): bool
    {
        return $this->is_active;
    }



    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithDiscount($query)
    {
        return $query->whereDate('discount_expires_at', '>', now());
    }
}

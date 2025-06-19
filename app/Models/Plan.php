<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'features',
        'duration_days',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function getCommissionPercent(): float
    {
        return $this->discount_expires_at?->isFuture()
            ? $this->discount_percent ?? 0
            : 0;
    }
}

<?php

<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Model;
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
        'type' => PlanTypeEnum::class,
        'features' => 'array',
        'discount_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getCommissionPercent(): float
    {
        return $this->discount_expires_at?->isFuture()
            ? ($this->discount_percent ?? 0)
            : 0;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

}

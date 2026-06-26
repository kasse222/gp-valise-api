<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LuggageCategoryEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TripCategoryFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'category',
        'fee',
    ];

    protected $casts = [
        'category' => LuggageCategoryEnum::class,
        'fee'      => 'integer',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}

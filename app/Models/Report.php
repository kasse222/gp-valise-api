<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'details',
    ];

    /**
     * 🔗 Utilisateur qui a effectué le signalement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔀 Élément signalé (polymorphique : Trip, Luggage, etc.)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}

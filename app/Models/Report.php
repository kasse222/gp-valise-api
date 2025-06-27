<?php

namespace App\Models;

use App\Enums\ReportReasonEnum;
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

    protected $casts = [
        'reason' => ReportReasonEnum::class, // 🧠 Enum pour sécuriser les motifs
    ];
    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Utilisateur ayant signalé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔀 Élément polymorphe signalé (Trip, Booking, Luggage…)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * 🛡️ Vérifie si un utilisateur est autorisé à consulter ce signalement
     */
    public function canBeViewedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin(); // méthode à implémenter
    }

    /**
     * 📌 Résumé compact (utile pour les listes)
     */
    public function summary(): string
    {
        return "{$this->reason->label()} - {$this->reportable_type} #{$this->reportable_id}";
    }
}

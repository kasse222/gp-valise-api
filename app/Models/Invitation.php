<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',         // L’utilisateur qui invite
        'recipient_email',   // Email de l’invité
        'token',             // Jeton d’invitation
        'used_at',           // Date d’utilisation
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 L’utilisateur ayant envoyé l’invitation
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * 🔍 Invitations non encore utilisées
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('used_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Invitation déjà utilisée ?
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * ⏱ Marque l’invitation comme utilisée maintenant
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}

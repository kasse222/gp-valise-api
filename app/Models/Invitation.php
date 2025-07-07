<?php

namespace App\Models;

use App\Enums\InvitationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',         // L’utilisateur qui invite
        'recipient_id',      // L’utilisateur invité (si déjà inscrit)
        'recipient_email',   // Email de l’invité
        'token',             // Jeton d’invitation unique
        'used_at',           // Date d’utilisation
        'expires_at',        // Date d’expiration (facultative)
        'status',
        'message',           // Message personnalisé (facultatif)
    ];

    protected $casts = [
        'used_at'    => 'datetime',
        'expires_at' => 'datetime',
        'status'       => InvitationStatusEnum::class,

    ];



    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */
    protected function isAuthorized(Request $request): bool
    {
        $user = $request->user();
        return $user && ($user->id === $this->sender_id || $user->is_admin);
    }

    /**
     * ✅ L’invitation a-t-elle été utilisée ?
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * ✅ L’invitation est-elle expirée ?
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Exemple de méthode métier
    public function isPending(): bool
    {
        return $this->status === InvitationStatusEnum::PENDING;
    }
    /**
     * ✅ L’invitation peut-elle encore être acceptée ?
     */
    public function canBeAccepted(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }

    /**
     * ⏱ Marque comme utilisée
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * 🕒 Durée restante en secondes avant expiration
     */
    public function timeLeft(): ?int
    {
        return $this->expires_at ? now()->diffInSeconds($this->expires_at, false) : null;
    }
}

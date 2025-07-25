<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Les attributs pouvant être assignés en masse.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'role',
        'verified_user',
        'phone',
        'country',
        'kyc_passed_at',
        'plan_id',
        'plan_expires_at',
    ];

    /**
     * Les attributs castés automatiquement.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_passed_at'      => 'datetime',
        'plan_expires_at'    => 'datetime',
        'phone_verified_at' => 'datetime',
        'verified_user'      => 'boolean',
        'role'               => UserRoleEnum::class,
    ];

    /**
     * Relation avec le plan de l'utilisateur.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
    public function luggages()
    {
        return $this->hasMany(Luggage::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'sender_id');
    }

    /**
     * Vérifie si l'utilisateur est premium.
     */
    public function isPremium(): bool
    {
        return $this->plan?->type === PlanTypeEnum::PREMIUM
            && $this->plan_expires_at?->isFuture();
    }
    public function isTrusted(): bool
    {
        return in_array($this->role, [
            UserRoleEnum::TRAVELER,
            UserRoleEnum::MODERATOR,
        ]);
    }


    /**
     * Vérifie si l'utilisateur a un type de plan donné et actif.
     */
    public function hasPlan(PlanTypeEnum $type): bool
    {
        return $this->plan?->type === $type
            && $this->plan_expires_at?->isFuture();
    }

    /**
     * Vérifie si l'utilisateur est administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }

    public function isExpeditor(): bool
    {
        return $this->role === UserRoleEnum::SENDER;
    }
    public function isVoyageur(): bool
    {
        return $this->role === UserRoleEnum::TRAVELER;
    }
    /**
     * Vérifie si l'utilisateur a validé son KYC.
     */
    public function hasKyc(): bool
    {
        return !is_null($this->kyc_passed_at);
    }
    public function sender(): self
    {
        return $this->state([
            'role' => UserRoleEnum::SENDER,
        ]);
    }

    public function traveler(): self
    {
        return $this->state([
            'role' => UserRoleEnum::TRAVELER,
        ]);
    }

    public function admin(): self
    {
        return $this->state([
            'role' => UserRoleEnum::ADMIN,
        ]);
    }
    /**
     * Vérifie si l’utilisateur est globalement vérifié (utilisé pour les invitations).
     */
    public function isVerified(): bool
    {
        return (bool) $this->verified_user;
    }
}

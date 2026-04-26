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


    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_passed_at'      => 'datetime',
        'plan_expires_at'    => 'datetime',
        'phone_verified_at' => 'datetime',
        'verified_user'      => 'boolean',
        'role'               => UserRoleEnum::class,
    ];


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



    public function hasPlan(PlanTypeEnum $type): bool
    {
        return $this->plan?->type === $type
            && $this->plan_expires_at?->isFuture();
    }


    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnum::ADMIN
            || $this->role === UserRoleEnum::SUPER_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }




    public function isExpeditor(): bool
    {
        return $this->role === UserRoleEnum::SENDER;
    }
    public function isVoyageur(): bool
    {
        return $this->role === UserRoleEnum::TRAVELER;
    }

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

    public function isVerified(): bool
    {
        return (bool) $this->verified_user;
    }
}

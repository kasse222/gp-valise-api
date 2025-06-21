<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Plan;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
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
        'kyc_passed_at' => 'datetime',
        'plan_expires_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPremium(): bool
    {
        return $this->plan?->type === PlanTypeEnum::PREMIUM
            && $this->plan_expires_at?->isFuture();
    }

    public function hasPlan(PlanTypeEnum $type): bool
    {
        return $this->plan?->type === $type
            && $this->plan_expires_at?->isFuture();
    }
}

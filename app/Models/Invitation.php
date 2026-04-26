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
        'sender_id',
        'recipient_id',
        'recipient_email',
        'token',
        'used_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'used_at'    => 'datetime',
        'expires_at' => 'datetime',
        'status'       => InvitationStatusEnum::class,

    ];





    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }




    public function scopeAvailable($query)
    {
        return $query->whereNull('used_at')
            ->where('status', InvitationStatusEnum::PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }


    protected function isAuthorized(Request $request): bool
    {
        $user = $request->user();
        return $user && ($user->id === $this->sender_id || $user->is_admin);
    }


    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatusEnum::PENDING;
    }

    public function canBeAccepted(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }


    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }


    public function timeLeft(): ?int
    {
        return $this->expires_at ? now()->diffInSeconds($this->expires_at, false) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_email',
        'token',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * 🔗 L’utilisateur qui a envoyé l’invitation
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * ✅ L’invitation a-t-elle été utilisée ?
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }
}

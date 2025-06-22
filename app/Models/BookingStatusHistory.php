<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',     // Référence à la réservation concernée
        'old_status',     // Enum BookingStatusEnum
        'new_status',     // Enum BookingStatusEnum
        'changed_by',     // User ID (admin ou acteur du changement)
        'reason',         // Texte libre : "annulation utilisateur", "confirmation", etc.
    ];

    protected $casts = [
        'old_status' => BookingStatusEnum::class,
        'new_status' => BookingStatusEnum::class,
    ];

    /**
     * 🔗 Réservation concernée
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * 🔗 Utilisateur ayant effectué le changement de statut
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * 🧾 Logger un changement de statut
     */
    public static function log(Booking $booking, BookingStatusEnum $old, BookingStatusEnum $new, User $user, ?string $reason = null): void
    {
        self::create([
            'booking_id' => $booking->id,
            'old_status' => $old, // 👈 ENUM directement
            'new_status' => $new, // 👈 ENUM directement
            'changed_by' => $user->id,
            'reason'     => $reason,
        ]);
    }
}

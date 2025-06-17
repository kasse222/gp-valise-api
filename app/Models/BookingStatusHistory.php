<?php

namespace App\Models;

use App\Status\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    protected $fillable = [
        'booking_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'old_status' => BookingStatus::class,
        'new_status' => BookingStatus::class,
    ];

    /**
     * Réservation concernée
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Utilisateur ayant effectué le changement  statut
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Logger un changement de statut
     */
    public static function log(Booking $booking, BookingStatus $old, BookingStatus $new, User $user, ?string $reason = null): void
    {
        self::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $new,
            'changed_by' => $user->id,
            'reason' => $reason,
        ]);
    }
}

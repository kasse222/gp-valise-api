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
        'booking_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'status' => BookingStatusEnum::class,
        'old_status' => BookingStatusEnum::class,
        'new_status' => BookingStatusEnum::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relations
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Réservation concernée
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * 🔗 Utilisateur ayant effectué le changement
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Méthodes Métier
    |--------------------------------------------------------------------------
    */

    /**
     * 🧾 Logger un changement de statut
     */
    public static function log(Booking $booking, BookingStatusEnum $from, BookingStatusEnum $to, User $user, ?string $reason = null): void
    {
        self::create([
            'booking_id' => $booking->id,
            'old_status' => $from,
            'new_status' => $to,
            'changed_by' => $user->id,
            'reason'     => $reason,
        ]);
    }

    /**
     * ✅ Transition manuelle ?
     */
    public function isManual(): bool
    {
        return filled($this->reason) && str($this->reason)->contains(['admin', 'manuel', 'override']);
    }

    /**
     * 🕒 Formatage date + transition
     */
    public function label(): string
    {
        return sprintf(
            '%s → %s par %s (%s)',
            $this->old_status->label(),
            $this->new_status->label(),
            optional($this->changedBy)->first_name ?? 'Système',
            $this->created_at?->format('d/m/Y H:i')
        );
    }
}

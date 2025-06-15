<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Luggage extends Model
{
    /** @use HasFactory<\Database\Factories\LuggageFactory> */
    use HasFactory;


    protected $table = 'luggages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'description',
        'weight_kg',
        'dimensions',
        'pickup_city',
        'delivery_city',
        'pickup_date',
        'delivery_date',
        'status',
    ];

    /**
     * Get the user (expéditeur) who owns the luggage.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking associated with this luggage.
     */
    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Scope to get only available luggages.
     */
    public function scopeDisponibles($query)
    {
        return $query->where('status', 'en_attente');
    }
}

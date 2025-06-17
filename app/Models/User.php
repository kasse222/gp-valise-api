<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'verified_user',       // ✅ Validation KYC
        'phone',               // ✅ Pour contact & vérif
        'country',             // ✅ Pays d’origine (diaspora)
        'kyc_passed_at',       // ✅ Date de vérif
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Status\UserRole::class,
        ];
    }

    // Relations existantes
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
    public function luggages()
    {
        return $this->hasMany(Luggage::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // Scopes pratiques
    public function scopeVoyageurs($query)
    {
        return $query->where('role', 'voyageur');
    }
    public function scopeExpediteurs($query)
    {
        return $query->where('role', 'expediteur');
    }
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }
}

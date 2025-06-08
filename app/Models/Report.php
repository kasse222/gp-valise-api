<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',      // Utilisateur à l'origine du signalement
        'target_type',  // Classe du modèle signalé : Trip, Booking, User…
        'target_id',    // ID du modèle signalé
        'reason',       // Motif principal du signalement
        'comment',      // Description plus détaillée
    ];

    /**
     * L'utilisateur qui a déposé le signalement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cible polymorphique du signalement : Trip, Booking, User, etc.
     */
    public function target()
    {
        return $this->morphTo();
    }
}

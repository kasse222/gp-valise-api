<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'features',
        'duration_days',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
    ];
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitlistEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'role',
        'message',
        'ip_address',
        'user_agent',
    ];
}

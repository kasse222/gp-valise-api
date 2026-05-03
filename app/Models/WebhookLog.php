<?php

namespace App\Models;

use App\Enums\WebhookLogStatusEnum;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'event_id',
        'correlation_id',
        'event',
        'provider_transaction_id',
        'status',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'status'       => WebhookLogStatusEnum::class,
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}

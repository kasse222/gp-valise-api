<?php

namespace App\Models;

use App\Enums\ReportReasonEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'details',
    ];

    protected $casts = [
        'reason' => ReportReasonEnum::class,
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }




    public function canBeViewedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }


    public function summary(): string
    {
        return "{$this->reason->label()} - {$this->reportable_type} #{$this->reportable_id}";
    }
}

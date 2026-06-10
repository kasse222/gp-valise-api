<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\KycRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycSubmitted
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly KycRequest $kycRequest) {}
}

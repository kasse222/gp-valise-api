<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DisputeMessage;
use Illuminate\Foundation\Events\Dispatchable;

final class DisputeMessageAdded
{
    use Dispatchable;

    public function __construct(
        public readonly DisputeMessage $message,
    ) {}
}

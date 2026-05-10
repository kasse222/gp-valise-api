<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\DisputeStatusEnum;
use App\Models\Dispute;
use Illuminate\Foundation\Events\Dispatchable;

final class DisputeStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Dispute           $dispute,
        public readonly ?DisputeStatusEnum $oldStatus, // ← nullable
        public readonly DisputeStatusEnum  $newStatus,
        public readonly ?string            $reason = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentEventData;
use Illuminate\Http\Request;

interface WebhookProcessorContract
{
    public function process(Request $request, string $providerKey): PaymentEventData;
}

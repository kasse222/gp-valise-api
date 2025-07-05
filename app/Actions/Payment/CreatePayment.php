<?php

namespace App\Actions\Payment;

use App\Models\User;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Str;

class CreatePayment
{
    public static function execute(User $user, array $data): Payment
    {
        return $user->payments()->create(array_merge([
            'status' => PaymentStatusEnum::EN_ATTENTE,
            'payment_reference' => strtoupper(Str::random(12)),
        ], $data));
    }
}

<?php

namespace App\Actions\Payment;

use App\Models\User;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;

class CreatePayment
{
    public static function execute(User $user, array $data): Payment
    {
        return $user->payments()->create([
            ...$data,
            'status' => PaymentStatusEnum::EN_ATTENTE,
        ]);
    }
}

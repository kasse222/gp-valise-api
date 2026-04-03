<?php

namespace App\Actions\Payment;

use App\Models\Payment;

class UpdatePayment
{
    public function execute(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->fresh();
    }
}

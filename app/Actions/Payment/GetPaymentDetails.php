<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Models\User;

class GetPaymentDetails
{
    public function execute(Payment $payment): Payment
    {
        return $payment;
    }
}

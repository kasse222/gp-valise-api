<?php

namespace App\Actions\Payment;

use App\Models\User;

class ListPayments
{
    public function execute(User $user)
    {
        return $user->payments()
            ->latest()
            ->paginate(10);
    }
}

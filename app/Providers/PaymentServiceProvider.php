<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentProvider;
use App\Services\Payments\FakePaymentProvider;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, FakePaymentProvider::class);
    }
}

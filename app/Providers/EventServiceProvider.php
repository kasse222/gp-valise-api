<?php

namespace App\Providers;

use App\Events\BookingConfirmed;
use App\Events\BookingExpired;
use App\Events\TransactionCreated;
use App\Events\TransactionRefunded;
use App\Listeners\LogBookingConfirmed;
use App\Listeners\LogBookingExpired;
use App\Listeners\LogTransactionCreated;
use App\Listeners\LogTransactionRefunded;
use App\Events\BookingCanceled;
use App\Events\BookingDelivered;
use App\Listeners\LogBookingCanceled;
use App\Listeners\LogBookingDelivered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingExpired::class => [
            LogBookingExpired::class,
        ],
        TransactionCreated::class => [
            LogTransactionCreated::class,
        ],

        TransactionRefunded::class => [
            LogTransactionRefunded::class,
        ],

        BookingConfirmed::class => [
            LogBookingConfirmed::class,
        ],
        BookingDelivered::class => [
            LogBookingDelivered::class,
        ],
        BookingCanceled::class => [
            LogBookingCanceled::class,
        ],

    ];
}

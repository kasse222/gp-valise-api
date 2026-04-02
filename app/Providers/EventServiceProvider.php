<?php

namespace App\Providers;

use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingExpired;
use App\Events\TransactionCreated;
use App\Events\TransactionRefunded;
use App\Listeners\LogBookingCompleted;
use App\Listeners\LogBookingConfirmed;
use App\Listeners\LogBookingExpired;
use App\Listeners\LogTransactionCreated;
use App\Listeners\LogTransactionRefunded;


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
        BookingCompleted::class => [
            LogBookingCompleted::class,
        ],

    ];
}

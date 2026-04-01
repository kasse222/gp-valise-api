<?php

namespace App\Providers;

use App\Events\BookingExpired;
use App\Listeners\LogBookingExpired;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingExpired::class => [
            LogBookingExpired::class,
        ],
    ];
}

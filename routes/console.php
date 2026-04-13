<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:expire-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('monitoring:webhooks')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('monitoring:queues')
    ->everyMinute()
    ->withoutOverlapping();

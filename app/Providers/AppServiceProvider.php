<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use App\Policies\LuggagePolicy;
use App\Policies\TripPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Booking::class => \App\Policies\BookingPolicy::class,
        Trip::class => TripPolicy::class,
        Luggage::class => LuggagePolicy::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

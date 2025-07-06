<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Models\{User, Booking, Trip, Luggage, Plan, Report, Payment, Transaction};
use App\Policies\{UserPolicy, BookingPolicy, TripPolicy, LuggagePolicy, PlanPolicy, ReportPolicy, PaymentPolicy, TransactionPolicy};

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class     => UserPolicy::class,
        Booking::class  => BookingPolicy::class,
        Trip::class     => TripPolicy::class,
        Luggage::class  => LuggagePolicy::class,
        Plan::class     => PlanPolicy::class,
        Report::class   => ReportPolicy::class,
        Payment::class  => PaymentPolicy::class,
        Transaction::class => TransactionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

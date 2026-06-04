<?php

namespace App\Providers;

use App\Events\BookingApproved;
use App\Events\BookingCanceled;
use App\Events\BookingConfirmed;
use App\Events\BookingDeclined;
use App\Events\BookingDelivered;
use App\Events\BookingDisputed;
use App\Events\BookingExpired;
use App\Events\DisputeMessageAdded;
use App\Events\DisputeStatusChanged;
use App\Events\TransactionCreated;
use App\Events\TransactionRefunded;
use App\Listeners\CreatePayoutAfterBookingDelivered;
use App\Listeners\LogBookingCanceled;
use App\Listeners\LogBookingConfirmed;
use App\Listeners\LogBookingDelivered;
use App\Listeners\LogBookingExpired;
use App\Listeners\LogTransactionCreated;
use App\Listeners\LogTransactionRefunded;
use App\Listeners\SendBookingApprovedNotification;
use App\Listeners\SendBookingCanceledNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Listeners\SendBookingDeclinedNotification;
use App\Listeners\SendBookingDeliveredNotification;
use App\Listeners\SendBookingExpiredNotification;
use App\Listeners\SendDisputeMessageAddedNotification;
use App\Listeners\SendDisputeOpenedNotification;
use App\Listeners\SendDisputeStatusChangedNotification;
use App\Listeners\SendTransactionRefundedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingApproved::class => [
            SendBookingApprovedNotification::class,
        ],

        BookingDeclined::class => [
            SendBookingDeclinedNotification::class,
        ],

        BookingConfirmed::class => [
            LogBookingConfirmed::class,
            SendBookingConfirmedNotification::class,
        ],

        BookingCanceled::class => [
            LogBookingCanceled::class,
            SendBookingCanceledNotification::class,
        ],

        BookingDelivered::class => [
            LogBookingDelivered::class,
            SendBookingDeliveredNotification::class,
        ],

        BookingExpired::class => [
            LogBookingExpired::class,
            SendBookingExpiredNotification::class,
        ],

        BookingDisputed::class => [
            SendDisputeOpenedNotification::class,
        ],

        TransactionCreated::class => [
            LogTransactionCreated::class,
        ],

        TransactionRefunded::class => [
            LogTransactionRefunded::class,
            SendTransactionRefundedNotification::class,
        ],

        DisputeStatusChanged::class => [
            SendDisputeStatusChangedNotification::class,
        ],

        DisputeMessageAdded::class => [
            SendDisputeMessageAddedNotification::class,
        ],
    ];
}

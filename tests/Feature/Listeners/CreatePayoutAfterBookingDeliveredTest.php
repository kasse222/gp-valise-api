<?php

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Events\BookingDelivered;
use App\Jobs\SendSlackAlert;
use App\Listeners\CreatePayoutAfterBookingDelivered;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('loggue un critical et dispatch SendSlackAlert quand le payout échoue', function () {
    Queue::fake();

    $actionMock = Mockery::mock(CreatePayoutTransaction::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->andThrow(ValidationException::withMessages(['booking' => 'Payout impossible.']));

    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'PAYOUT ÉCHOUÉ après livraison booking'
                && isset($context['booking_id'])
                && isset($context['error']);
        });

    $traveler = User::factory()->traveler()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);
    $booking  = Booking::factory()->create(['trip_id' => $trip->id]);

    $listener = new CreatePayoutAfterBookingDelivered($actionMock);
    $listener->handle(new BookingDelivered($booking));

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Payout échoué après livraison'
            && $job->level === 'critical';
    });
});

it('n\'envoie pas d\'alerte quand le payout réussit', function () {
    Queue::fake();

    $actionMock = Mockery::mock(CreatePayoutTransaction::class);
    $actionMock->shouldReceive('execute')->once()->andReturn(Transaction::factory()->make());

    $traveler = User::factory()->traveler()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);
    $booking  = Booking::factory()->create(['trip_id' => $trip->id]);

    $listener = new CreatePayoutAfterBookingDelivered($actionMock);
    $listener->handle(new BookingDelivered($booking));

    Queue::assertNothingPushed();
});

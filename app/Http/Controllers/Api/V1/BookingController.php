<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\DeleteBooking;
use App\Actions\Booking\GetBookingDetails;
use App\Actions\Booking\GetUserBookings;
use App\Actions\Booking\ReserveBooking;
use App\Actions\Transaction\CreateTransaction;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
    use AuthorizesRequests;


    public function index(Request $request, GetUserBookings $action)
    {
        $bookings = $action->execute($request->user());

        return BookingResource::collection($bookings);
    }


    public function store(StoreBookingRequest $request, ReserveBooking $action)
    {
        $this->authorize('create', Booking::class);

        $booking = $action->execute(
            $request->user(),
            $request->validated()
        );
        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }


    public function show(Booking $booking, GetBookingDetails $action)
    {
        $this->authorize('view', $booking);

        $booking = $action->execute($booking->id);

        return new BookingResource($booking->loadMissing('bookingItems.luggage'));
    }


    public function destroy(Booking $booking, DeleteBooking $action)
    {
        $this->authorize('delete', $booking);

        $action->execute($booking);

        return response()->json([
            'message' => 'Réservation supprimée.',
        ]);
    }


    public function confirm(Request $request, Booking $booking, ConfirmBooking $action)
    {
        $this->authorize('confirm', $booking);

        $booking = $action->execute($booking, $request->user());

        return new BookingResource($booking->load('bookingItems.luggage'));
    }


    public function cancel(Request $request, Booking $booking, CancelBooking $action)
    {
        $this->authorize('cancel', $booking);

        $booking = $action->execute($booking, $request->user());

        return new BookingResource(
            $booking->loadMissing('bookingItems.luggage')
        );
    }


    public function complete(Request $request, Booking $booking, CompleteBooking $action)
    {
        $this->authorize('complete', $booking);

        $booking = $action->execute($booking, $request->user());

        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => new BookingResource($booking->load('bookingItems.luggage')),
        ]);
    }

    public function pay(Request $request, Booking $booking, CreateTransaction $action)
    {
        $this->authorize('view', $booking);

        $booking->loadMissing('bookingItems');

        $totalCentimes = $booking->bookingItems->sum('price');

        if ($totalCentimes <= 0) {
            return response()->json([
                'message' => 'Aucun montant à payer pour cette réservation.',
            ], 422);
        }

        $transaction = $action->execute($request->user(), [
            'booking_id'     => $booking->id,
            'amount'         => $totalCentimes / 100,
            'currency'       => 'EUR',
            'method'         => 'card',
            'country'        => $request->user()->country ?? 'FR',
            'correlation_id' => $request->header('X-Correlation-ID'),
        ]);

        // Si FakeProvider → COMPLETED immédiat → confirmer le booking
        if ($transaction->status === \App\Enums\TransactionStatusEnum::COMPLETED) {
            $bookingFresh = $booking->fresh(['trip']);
            $traveler = $bookingFresh->trip->user;
            app(\App\Actions\Booking\ConfirmBooking::class)
                ->execute($bookingFresh, $traveler);
        }

        return response()->json([
            'transaction_id' => $transaction->id,
            'booking_id'     => $booking->id,
            'amount'         => $transaction->amount,
            'status'         => $transaction->status,
            'payment_url'    => null,
        ], 201);
    }
}

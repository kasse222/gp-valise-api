<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\ApproveBooking;
use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\ConfirmBooking;
use App\Actions\Booking\DeclineBooking;
use App\Actions\Booking\DeleteBooking;
use App\Actions\Booking\GetBookingDetails;
use App\Actions\Booking\GetUserBookings;
use App\Actions\Booking\ReserveBooking;
use App\Actions\Transaction\CreateTransaction;
use App\Http\Requests\Booking\PayBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
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

    public function approve(Request $request, Booking $booking, ApproveBooking $action): JsonResponse
    {
        $booking->loadMissing('trip');
        $this->authorize('approve', $booking);

        $booking = $action->execute($booking, $request->user());

        return response()->json([
            'message' => 'Réservation approuvée.',
            'data'    => new BookingResource($booking),
        ]);
    }

    public function decline(Request $request, Booking $booking, DeclineBooking $action): JsonResponse
    {
        $booking->loadMissing('trip');
        $this->authorize('decline', $booking);

        $booking = $action->execute($booking, $request->user());

        return response()->json([
            'message' => 'Réservation refusée.',
            'data'    => new BookingResource($booking),
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
        $booking->loadMissing('trip');
        $this->authorize('complete', $booking);

        $booking = $action->execute($booking, $request->user());

        return response()->json([
            'message' => 'Réservation livrée avec succès.',
            'booking' => new BookingResource($booking->load('bookingItems.luggage')),
        ]);
    }

    public function pay(PayBookingRequest $request, Booking $booking, CreateTransaction $action)
    {
        $this->authorize('view', $booking);

        if ($request->user()->isExpeditor() && ! $request->user()->hasKyc()) {
            return response()->json([
                'message' => 'Vérification d\'identité (KYC) requise avant de procéder au paiement.',
                'kyc_required' => true,
            ], 403);
        }

        $booking->loadMissing('bookingItems');
        $totalCentimes = $booking->bookingItems->sum('price');

        if ($totalCentimes <= 0) {
            return response()->json(['message' => 'Aucun montant à payer.'], 422);
        }

        $country = strtoupper($request->input('country') ?? $request->user()->country ?? 'FR');
        $method  = $request->input('method', 'card');
        $phone   = $request->input('phone');

        $transaction = $action->execute($request->user(), [
            'booking_id'     => $booking->id,
            'amount'         => $totalCentimes,  // ← centimes entiers, pas de division
            'currency'       => 'EUR',
            'method'         => $method,
            'country'        => $country,
            'phone'          => $phone,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ]);

        if ($transaction->status === \App\Enums\TransactionStatusEnum::COMPLETED) {
            $bookingFresh = $booking->fresh(['trip']);
            app(\App\Actions\Booking\ConfirmBooking::class)
                ->execute($bookingFresh, $bookingFresh->trip->user);
        }

        return response()->json([
            'transaction_id' => $transaction->id,
            'booking_id'     => $booking->id,
            'amount'         => $transaction->amount,
            'status'         => $transaction->status,
            'payment_url'    => $transaction->checkout_url ?? null,
        ], 201);
    }
}
